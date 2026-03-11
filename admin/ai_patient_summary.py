import mysql.connector
import json
import sys
from datetime import date, datetime
from decimal import Decimal
from openai import OpenAI

# ---------------- CONFIGURATION ----------------
# Standard Local Ollama Config
OLLAMA_API_URL = "http://localhost:11434/v1"
OLLAMA_MODEL = "llama3"

# Database Config
DB_CONFIG = {
    'host': "localhost",
    'user': "root",
    'password': "",
    'database': "med_buddy_db"
}

# Force UTF-8 encoding for Windows console
if sys.platform.startswith('win'):
    sys.stdout.reconfigure(encoding='utf-8')

# -----------------------------------------------

class PatientDataManager:
    def __init__(self, db_config):
        self.db_config = db_config

    def get_connection(self):
        return mysql.connector.connect(**self.db_config)

    def fetch_raw_patient_data(self, patient_id):
        conn = self.get_connection()
        cursor = conn.cursor(dictionary=True)
        try:
            # 1. Fetch Demographics
            cursor.execute("SELECT * FROM patients WHERE patient_id = %s", (patient_id,))
            patient = cursor.fetchone()
            if not patient: return None

            # 2. Fetch Documents
            try:
                cursor.execute("""SELECT image_type, description, file_path, taken_at
             FROM patient_images
WHERE patient_id = %s
""", (patient_id,))
                images = cursor.fetchall()
            except: images = []

            # 3. Serialize Dates/Decimals
            for key, val in patient.items():
                if isinstance(val, (date, datetime)): patient[key] = str(val)
                elif isinstance(val, Decimal): patient[key] = float(val)
            
            for img in images:
                if isinstance(img['taken_at'], (date, datetime)): img['taken_at'] = str(img['taken_at'])

            documents = []

            for img in images:
                doc_type = img.get("image_type", "Document")
                img_date = img.get("taken_at", "Unknown date")
                path = img.get("file_path", "")

                if path:
                    link = f"http://localhost/Med-Buddy/uploads/{path}"
                else:
                    link = "No file available"
                
                documents.append({
                    "type": doc_type,
                    "date": img_date,
                    "link": link
                })

            return {
                "personal_details": patient,
                "uploaded_documents": documents
            }
        except: return None
        finally:
            if conn.is_connected(): cursor.close(); conn.close()
def summarize_reports(text):
    client = OpenAI(base_url=OLLAMA_API_URL, api_key="ollama")

    prompt = f"""
Summarize the following laboratory reports for a doctor.
Highlight abnormal findings only.

{text}
"""

    response = client.chat.completions.create(
        model=OLLAMA_MODEL,
        messages=[{"role": "user", "content": prompt}],
        temperature=0.2,
    )

    return response.choices[0].message.content
def summarize_prescriptions(text):
    client = OpenAI(base_url=OLLAMA_API_URL, api_key="ollama")

    prompt = f"""
Extract medicines from this prescription.

Return format:
Medicine | Dose | Frequency

{text}
"""

    response = client.chat.completions.create(
        model=OLLAMA_MODEL,
        messages=[{"role": "user", "content": prompt}],
        temperature=0.2,
    )

    return response.choices[0].message.content
def summarize_scans(text):
    client = OpenAI(base_url=OLLAMA_API_URL, api_key="ollama")

    prompt = f"""
Summarize this scan report in 2 sentences.

{text}
"""

    response = client.chat.completions.create(
        model=OLLAMA_MODEL,
        messages=[{"role": "user", "content": prompt}],
        temperature=0.2,
    )

    return response.choices[0].message.content

def generate_ai_summary(patient_data):
    """
    Attempts to use Ollama Local AI with Streaming.
    """
    client = OpenAI(base_url=OLLAMA_API_URL, api_key="ollama")
    
    system_instruction = """
You are a hospital AI assistant.

Generate a clinical summary for doctors strictly using the following headings:
**Patient Information**
**Medical Conditions**
**Allergies**
**Uploaded Medical Records**
**Risk Notes**
**Emergency Contact**

When listing 'Uploaded Medical Records', ALWAYS output clickable markdown hyperlinks to the documents.
Format it EXACTLY like this: - [View Document Type](URL)

Only use the links provided in the data. Do not use '#' for headings, just use bold text. Do not mention dates in the document names. Do not invent information.
"""
    
    try:
        stream = client.chat.completions.create(
            model=OLLAMA_MODEL,
            messages=[
                {"role": "system", "content": system_instruction},
                {"role": "user", "content": json.dumps(patient_data)}
            ],
            temperature=0.3,
            stream=True
        )
        
        full_content = ""
        for chunk in stream:
            if chunk.choices[0].delta.content:
                content = chunk.choices[0].delta.content
                print(content, end='', flush=True)
                full_content += content
        
        return full_content if full_content else None
    except Exception as e:
        # For debugging purposes if needed, though we return None for fallback
        # sys.stderr.write(f"Streaming error: {str(e)}\n")
        return None  # Return None to trigger fallback

def generate_smart_template_summary(data):
    """
    A high-quality RULE-BASED summary that mimics AI output.
    Used when AI services are offline.
    """
    p = data.get('personal_details', {})
    docs = data.get('uploaded_documents', [])
    
    # Calculate Age
    age = "Unknown"
    if p.get('date_of_birth'):
        try:
            b_date = datetime.strptime(str(p['date_of_birth']), '%Y-%m-%d')
            age_num = 2026 - b_date.year
            age = f"{age_num} years"
        except: pass

    # Construct the "AI-Like" Narrative
    summary = ""
    
    # 1. Patient Information
    summary += "**Patient Information**\n"
    summary += f"- **Name:** {p.get('full_name', 'N/A')}\n"
    summary += f"- **Age:** {age}\n"
    summary += f"- **Gender:** {p.get('gender', 'N/A')}\n"
    summary += f"- **Blood Group:** {p.get('blood_group', 'N/A')}\n"
    summary += f"- **Height:** {p.get('height', 'N/A')} cm\n"
    summary += f"- **Weight:** {p.get('weight', 'N/A')} kg\n"
    summary += f"- **Contact:** {p.get('phone_number', 'N/A')}\n\n"
    
    # 2. Medical Conditions
    summary += "**Medical Conditions**\n"
    conditions = p.get('chronic_conditions', 'None reported')
    summary += f"{conditions}\n\n"
        
    # 3. Allergies
    summary += "**Allergies**\n"
    allergies = p.get('allergies', 'None')
    summary += f"{allergies}\n\n"

    # 4. Uploaded Medical Records
    summary += "**Uploaded Medical Records**\n"
    if docs:
        for d in docs:
            desc = d.get('type', 'Document')
            link = d.get('link', '#')
            summary += f"- [View {desc}]({link})\n"
    else:
        summary += "No medical documents or imaging uploaded.\n"

    # 5. Risk Notes
    summary += "\n**Risk Notes**\n"
    # Basic rule-based risk induction
    if 'Diabetes' in str(conditions) or 'Hypertension' in str(conditions):
        summary += "- High cardiovascular risk profile.\n"
    elif 'Asthma' in str(conditions):
        summary += "- Respiratory monitoring advised.\n"
    else:
        summary += "- No immediate critical risks identified from record.\n"

    # 6. Emergency Contact
    summary += "\n**Emergency Contact**\n"
    e_name = p.get('emergency_contact_name', 'N/A')
    e_phone = p.get('emergency_contact_phone', 'N/A')
    summary += f"- **Name:** {e_name}\n"
    summary += f"- **Phone:** {e_phone}\n"

    summary += "\n\n_Generated by Med-Buddy Intelligence System_"
    return summary

# --- MAIN EXECUTION ---
if __name__ == "__main__":
    if len(sys.argv) < 2: sys.exit(1)
    
    pid = sys.argv[1].strip()
    
    try:
        data_mgr = PatientDataManager(DB_CONFIG)
        raw_data = data_mgr.fetch_raw_patient_data(pid)
        
        if raw_data:
            # 1. Try Real Local AI (this will print chunks to stdout)
            summary = generate_ai_summary(raw_data)
            
            # 2. If AI failed/offline, use Smart Template
            if not summary:
                summary = generate_smart_template_summary(raw_data)
                print(summary)
        else:
            print(f"No records found for {pid}")
            
    except Exception as e:
        print(f"System Error: {str(e)}")
