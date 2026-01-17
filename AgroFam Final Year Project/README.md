# AgroFam Setup Instructions

## Backend Setup

1. **Import Database**
   - Import the database named `registration` using XAMPP or WAMP server for local development.

2. **Run Flask App**
   - Start the Flask application.
   - Ensure the model paths specified in your code are correct.

## Frontend Setup

1. **Install Composer Dependencies**
   - Make sure Composer is installed.
   - Navigate to the frontend folder and run:
     ```
     composer install
     ```

2. **Bidding Feature**
   - For bidding functionality:
     - Obtain a Gmail API key from [Google Cloud Console](https://console.cloud.google.com/).
     - Ensure both Apache and MySQL are running.

## Modules

- **Disease Detection Module**
  - Run:
    ```
    python app.py
    ```
- **Fertilizer Recommendation Module**
  - Run:
    ```
    python fertilizer.py
    ```
- **Crop Recommendation Module**
  - Run:
    ```
    python crop.py
    ```

---

**Notes:**
- Ensure all required dependencies are installed for both the backend (Python/Flask) and frontend (PHP/Composer).
- Check configuration files for proper API key placement and model paths.

