from flask import Flask, request, jsonify
import pickle
import pandas as pd
from flask_cors import CORS


app = Flask(__name__)
CORS(app)  


try:
    with open("models/rf_model.pkl", "rb") as file:
        model = pickle.load(file)
except FileNotFoundError:
    print("Error: Model file 'rf_model.pkl' not found!")
    exit(1)
except Exception as e:
    print(f"Error loading model: {e}")
    exit(1)


soil_type_mapping = {
    "Sandy": 0, "Loamy": 1, "Black": 2, "Red": 3, "Clayey": 4
}


crop_type_mapping = {
    "Rice": 0, "Wheat": 1, "Tobacco": 2, "Sugarcane": 3, "Pulses": 4, "Pomegranate": 5,
    "Paddy": 6, "Oil seeds": 7, "Millets": 8, "Maize": 9, "Groundnut": 10, "Cotton": 11,
    "Coffee": 12, "Watermelon": 13, "Barley": 14, "Kidney beans": 15, "Orange": 16
}


fertilizer_mapping = {
    0: "Urea", 1: "TSP", 2: "Superphosphate", 3: "Potassium sulfate",
    4: "Potassium chloride", 5: "DAP", 6: "28-28", 7: "20-20",
    8: "17-17-17", 9: "15-15-15", 10: "14-35-14", 11: "14-14-14",
    12: "10-26-26", 13: "10-10-10"
}


feature_names = [
    "Temparature", "Humidity", "Moisture", "Soil_Type", "Crop_Type",
    "Nitrogen", "Potassium", "Phosphorous"
]

@app.route("/fertilizer-predict", methods=["POST"])
def predict_fertilizer():
    try:
        
        input_data = request.json

        
        required_fields = ["temperature", "humidity", "moisture", "soil_type", "crop_type", "N", "P", "K"]
        missing_fields = [field for field in required_fields if field not in input_data]
        if missing_fields:
            return jsonify({"error": f"Missing fields: {', '.join(missing_fields)}"}), 400

        
        try:
            temperature = float(input_data["temperature"])
            humidity = float(input_data["humidity"])
            moisture = float(input_data["moisture"])
            nitrogen = float(input_data["N"])
            phosphorus = float(input_data["P"])
            potassium = float(input_data["K"])
        except ValueError:
            return jsonify({"error": "Invalid numerical values provided."}), 400

        
        soil_type = input_data["soil_type"]
        if soil_type not in soil_type_mapping:
            return jsonify({"error": "Invalid Soil Type. Choose from: Sandy, Loamy, Black, Red, Clayey"}), 400

       
        crop_type = input_data["crop_type"]
        if crop_type not in crop_type_mapping:
            return jsonify({"error": "Invalid Crop Type. Choose from available options."}), 400

        
        soil_type_num = soil_type_mapping[soil_type]
        crop_type_num = crop_type_mapping[crop_type]

        
        numerical_data = pd.DataFrame([[temperature, humidity, moisture, soil_type_num, crop_type_num, nitrogen, potassium, phosphorus]],
                                      columns=feature_names)

        
        try:
           prediction_num = model.predict(numerical_data)[0]
           print(f"Raw model prediction: {prediction_num}")  

    
           if isinstance(prediction_num, str):  
                predicted_fertilizer = prediction_num  
           else:
                predicted_fertilizer = fertilizer_mapping.get(int(prediction_num), "Unknown Fertilizer")

           return jsonify({"fertilizer": str(predicted_fertilizer)})

        except Exception as e:
            return jsonify({"error": f"Model prediction failed: {e}"}), 500

    except Exception as e:
        return jsonify({"error": str(e)}), 500

if __name__ == "__main__":
    app.run(debug=True)
