from flask import Flask, request, jsonify
from flask_cors import CORS
import pickle
import pandas as pd
import numpy as np


with open("models/model.pkl", "rb") as file:
    model = pickle.load(file)


app = Flask(__name__)
CORS(app)  

@app.route("/predict", methods=["POST"])
def predict():
    try:
        data = request.get_json()
        print(f"🛠 Received Data: {data}") 
        
        N = float(data["N"])
        P = float(data["P"])
        K = float(data["K"])
        temperature = float(data["temperature"])
        humidity = float(data["humidity"])
        ph = float(data["ph"])
        rainfall = float(data["rainfall"])

        
        feature_names = ['N', 'P', 'K', 'temperature', 'humidity', 'ph', 'rainfall']
        input_data = pd.DataFrame([[N, P, K, temperature, humidity, ph, rainfall]], columns=feature_names)

        print(f"🔍 Input DataFrame: \n{input_data}") 

        
        prediction = model.predict(input_data)[0]

        print(f"🌱 Predicted Crop: {prediction}")  

        
        crop_dict = {1: "Rice", 2: "Maize", 3: "Jute", 4: "Cotton", 5: "Coconut",
                     6: "Papaya", 7: "Orange", 8: "Apple", 9: "Muskmelon", 10: "Watermelon",
                     11: "Grapes", 12: "Mango", 13: "Banana", 14: "Pomegranate",
                     15: "Lentil", 16: "Blackgram", 17: "Mungbean", 18: "Mothbeans",
                     19: "Pigeonpeas", 20: "Kidneybeans", 21: "Chickpea", 22: "Coffee",
                     "rice": "Rice", "maize": "Maize", "jute": "Jute", "cotton": "Cotton", "coconut": "Coconut",
                     "papaya": "Papaya", "orange": "Orange", "apple": "Apple", "muskmelon": "Muskmelon", "watermelon": "Watermelon",
                     "grapes": "Grapes", "mango": "Mango", "banana": "Banana", "pomegranate": "Pomegranate",
                     "lentil": "Lentil", "blackgram": "Blackgram", "mungbean": "Mungbean", "mothbeans": "Mothbeans",
                     "pigeonpeas": "Pigeonpeas", "kidneybeans": "Kidneybeans", "chickpea": "Chickpea", "coffee": "Coffee"}

        
        crop_name = crop_dict.get(prediction, f"Unknown Crop ({prediction})")

        return jsonify({"crop": crop_name})  

    except Exception as e:
        print(f"❌ Error: {str(e)}")  
        return jsonify({"error": str(e)}), 400


if __name__ == "__main__":
    app.run(debug=True)
