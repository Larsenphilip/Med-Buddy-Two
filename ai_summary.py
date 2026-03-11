import sys
import mysql.connector
import requests
print("NEW AI SCRIPT RUNNING")

patient_id = sys.argv[1]

# Connect database
conn = mysql.connector.connect(
    host="localhost",
    user="root",
    password="",
    database="health_management_db"
)

cursor = conn.cursor(dictionary=True)

# Get patient details
cursor.execute("SELECT * FROM patients WHERE patient_id=%s", (patient_id,))
patient = cursor.fetchone()

# Get reports / images
cursor.execute("SELECT * FROM patient_images WHERE patient_id=%s", (patient_id,))
images = cursor.fetchall()

data = f"""
Patient Name: {patient['full_name']}
Blood Group: {patient['blood_group']}
Conditions: {patient['chronic_conditions']}
Allergies: {patient['allergies']}
Emergency Contact: {patient['emergency_contact_name']} ({patient['emergency_contact_phone']})
"""

for img in images:
    data += f"\nDocument Type: {img['image_type']} | Description: {img['description']}"

prompt = f"""
You are a clinical assistant in a hospital system.

Generate a clear medical summary for doctors.

Return sections:

Patient Overview
Medical Conditions
Allergies
Reports
Prescriptions
Scans
Risk Notes
Emergency Contact

Patient Data:
{data}
"""

response = requests.post(
    "http://localhost:11434/api/generate",
    json={
        "model": "llama3",
        "prompt": prompt,
        "stream": False
    }
)

print(response.json()["response"])