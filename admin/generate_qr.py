import qrcode
import os
import sys
import mysql.connector
import json

# ---------------- CONFIGURATION ----------------
DB_CONFIG = {
    'host': "localhost",
    'user': "root",
    'password': "",
    'database': "med_buddy_db"
}

UPLOADS_FOLDER = os.path.join(os.path.dirname(os.path.dirname(__file__)), "uploads", "qrcodes")
os.makedirs(UPLOADS_FOLDER, exist_ok=True)

# -----------------------------------------------

def get_patient_data(patient_id):
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        cursor = conn.cursor(dictionary=True)
        
        query = """
            SELECT patient_id, blood_group, allergies, chronic_conditions, emergency_contact_name, emergency_contact_phone
            FROM patients
            WHERE patient_id = %s
        """
        cursor.execute(query, (patient_id,))
        result = cursor.fetchone()
        
        cursor.close()
        conn.close()
        return result
    except Exception as e:
        sys.stderr.write(f"DB Error: {str(e)}\n")
        return None

def generate_qr(patient_data):
    if not patient_data:
        return None

    patient_id = patient_data['patient_id']
    
    def clean(val, default="N/A"):
        return str(val) if val is not None and str(val).strip() != "" else default

    # Construct the data string for the QR code
    qr_data = f"Patient ID: {patient_id}\n"
    qr_data += f"Blood Group: {clean(patient_data.get('blood_group'))}\n"
    qr_data += f"Allergies: {clean(patient_data.get('allergies'), 'None')}\n"
    qr_data += f"Chronic Diseases: {clean(patient_data.get('chronic_conditions'), 'None')}\n"
    qr_data += f"Emergency Contact: {clean(patient_data.get('emergency_contact_name'))} ({clean(patient_data.get('emergency_contact_phone'))})"

    qr = qrcode.QRCode(
        version=1,
        error_correction=qrcode.constants.ERROR_CORRECT_M,
        box_size=10,
        border=4
    )
    qr.add_data(qr_data)
    qr.make(fit=True)

    img = qr.make_image(fill_color="black", back_color="white")
    
    file_name = f"{patient_id}_qr.png"
    save_path = os.path.join(UPLOADS_FOLDER, file_name)
    img.save(save_path)
    
    return f"uploads/qrcodes/{file_name}"

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print(json.dumps({"success": False, "error": "Missing patient ID"}))
        sys.exit(1)
        
    patient_id = sys.argv[1].strip()
    data = get_patient_data(patient_id)
    
    if data:
        url = generate_qr(data)
        if url:
            print(json.dumps({"success": True, "qr_url": url}))
        else:
            print(json.dumps({"success": False, "error": "Failed to generate QR image"}))
    else:
        print(json.dumps({"success": False, "error": "Patient not found"}))
