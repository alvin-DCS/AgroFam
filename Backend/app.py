from flask import Flask, request, jsonify, render_template
from flask_cors import CORS
from ultralytics import YOLO
import cv2
import numpy as np
import pandas as pd
import os

app = Flask(__name__)
CORS(app, resources={r"/*": {"origins": "*"}})


MODEL_PATH = "models/bestgoo.pt"
model = YOLO(MODEL_PATH)


disease_info = pd.read_csv("disease_info.csv",encoding='cp1252')
supplement_info = pd.read_csv("supplement_info.csv",encoding='cp1252')


class_names = {
    0: 'Apple Black Rot Leaf', 1: 'Apple Healthy Leaf', 2: 'Apple Scab Leaf',
    3: 'Bell Pepper Bacterial Spot Leaf', 4: 'Bell Pepper Healthy Leaf',
    5: 'Cassava Brown Streak Disease', 6: 'Cassava Bacterial Blight',
    7: 'Cassava Green Mottle', 8: 'Cedar Apple Rust', 9: 'Cherry Healthy Leaf',
    10: 'Cherry Powdery Mildew Leaf', 11: 'Corn Cercospora Leaf Spot',
    12: 'Corn Common Rust Leaf', 13: 'Corn Healthy Leaf', 14: 'Grape Black Rot Leaf',
    15: 'Grape Esca Leaf', 16: 'Grape Healthy Leaf', 17: 'Grape Leaf Blight',
    18: 'Cassava Healthy Leaf', 19: 'Cassava Mosaic Virus Leaf',
    20: 'Northern Leaf Blight', 21: 'Orange Citrus Greening',
    22: 'Peach Bacterial Spot Leaf', 23: 'Peach Healthy Leaf',
    24: 'Potato Early Blight Leaf', 25: 'Potato Healthy Leaf',
    26: 'Potato Late Blight Leaf', 27: 'Rice Brown Spot', 28: 'Rice Healthy Leaf',
    29: 'Rice Hispa Leaf', 30: 'Rice Leaf Blast', 31: 'Spider Mites Two-Spotted Spider Mite Leaf',
    32: 'Squash Powdery Mildew Leaf', 33: 'Strawberry Healthy Leaf',
    34: 'Strawberry Leaf Scorch', 35: 'Tomato Bacterial Spot Leaf',
    36: 'Tomato Early Blight Leaf', 37: 'Tomato Late Blight Leaf',
    38: 'Tomato Healthy Leaf', 39: 'Tomato Leaf Mould',
    40: 'Tomato Septoria Leaf Spot'
}

crop_class_mapping = {
    'Apple': [0, 1, 2, 8],
    'Bell Pepper': [3, 4],
    'Cassava': [5, 6, 7, 18, 19],
    'Cherry': [9, 10],
    'Corn': [11, 12, 13, 20],
    'Grape': [14, 15, 16, 17],
    'Orange': [21],
    'Peach': [22, 23],
    'Potato': [24, 25, 26],
    'Rice': [27, 28, 29, 30],
    'Other':[31, 32],
    'Strawberry': [33, 34],
    'Tomato': [35, 36, 37, 38, 39, 40]
}


def apply_clahe(image):
    """Enhance image contrast using CLAHE."""
    img_lab = cv2.cvtColor(image, cv2.COLOR_BGR2LAB)
    l, a, b = cv2.split(img_lab)
    clahe = cv2.createCLAHE(clipLimit=2.0, tileGridSize=(8, 8))
    l_clahe = clahe.apply(l)
    img_lab_clahe = cv2.merge((l_clahe, a, b))
    img_clahe = cv2.cvtColor(img_lab_clahe, cv2.COLOR_LAB2BGR)
    return img_clahe

def detect_with_yolov8(image, crop=None):
    results = model.predict(source=image, conf=0.5)
    detections = []

    allowed_ids = crop_class_mapping.get(crop, None)
    found_valid = False

    for result in results:
        for box in result.boxes:
            class_id = int(box.cls[0])

           
            if allowed_ids and class_id not in allowed_ids:
                continue  

            found_valid = True
            confidence = float(box.conf[0])
            bbox = box.xyxy[0].tolist()
            class_name = class_names.get(class_id, "Unknown")

            disease_details = disease_info[disease_info['disease_name'] == class_name]
            prevent = disease_details['Possible Steps'].iloc[0] if not disease_details.empty else "No prevention steps available"
            image_url = disease_details['image_url'].iloc[0] if not disease_details.empty else ""
            disease_desc = disease_details['description'].values[0] if not disease_details.empty else "No information available"

            supplement_details = supplement_info[supplement_info['disease_name'] == class_name]
            supplement_rec = supplement_details['supplement name'].iloc[0] if not supplement_details.empty else "No supplement available"
            supplement_image_url = supplement_details['supplement image'].iloc[0] if not supplement_details.empty else ""
            supplement_buy_link = supplement_details['buy link'].values[0] if not supplement_details.empty else ""

            detections.append({
                "class_id": class_id,
                "class_name": class_name,
                "confidence": confidence,
                "bbox": bbox,
                "disease_info": {
                    "name": class_name,
                    "description": disease_desc,
                    "prevention": prevent,
                    "image_url": image_url
                },
                "supplement_info": {
                    "name": supplement_rec,
                    "image_url": supplement_image_url,
                    "buy_link": supplement_buy_link
                }
            })

    
    has_any_detection = any(len(result.boxes) > 0 for result in results)

    if not found_valid and has_any_detection:
        print("[INFO] No matching disease for selected crop. Returning 'healthy'.")
        return "healthy"

    if not has_any_detection:
        print("[INFO] No detections at all. Returning 'healthy'.")
        return "healthy"

    return detections


@app.route("/")
def home():
    return "🚀 Plant Disease Detection API is Running!"

@app.route("/detect", methods=["POST"])
def detect():
    if "image" not in request.files:
        return jsonify({"error": "No image uploaded"}), 400

    crop = request.form.get("crop")
    file = request.files["image"]
    image = cv2.imdecode(np.frombuffer(file.read(), np.uint8), cv2.IMREAD_COLOR)
    preprocessed_image = apply_clahe(image)

    predictions = detect_with_yolov8(preprocessed_image, crop)

    if predictions == "healthy":
        healthy_label = f"{crop} Healthy Leaf"

        disease_details = disease_info[disease_info['disease_name'] == healthy_label]
        supplement_details = supplement_info[supplement_info['disease_name'] == healthy_label]

        healthy_data = {
            "class_id": -1,
            "class_name": healthy_label,
            "confidence": 1.0,
            "bbox": [],
            "disease_info": {
                "name": healthy_label,
                "description": disease_details['description'].values[0] if not disease_details.empty else "This leaf is healthy.",
                "prevention": disease_details['Possible Steps'].values[0] if not disease_details.empty else "No prevention needed.",
                "image_url": disease_details['image_url'].values[0] if not disease_details.empty else ""
            },
            "supplement_info": {
                "name": supplement_details['supplement name'].values[0] if not supplement_details.empty else "No supplement needed.",
                "image_url": supplement_details['supplement image'].values[0] if not supplement_details.empty else "",
                "buy_link": supplement_details['buy link'].values[0] if not supplement_details.empty else ""
            }
        }

        return jsonify({
            "detections": [healthy_data],
            "message": healthy_label
        })

    elif predictions:
        return jsonify({"detections": predictions})
    else:
        return jsonify({"detections": [], "message": "No disease detected"}), 200



if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5000, debug=True)
