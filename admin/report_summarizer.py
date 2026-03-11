import sys
import json
import requests
import mysql.connector
import os

# ---------------- CONFIGURATION ----------------
OLLAMA_API_URL = "http://localhost:11434/api/generate"
OLLAMA_MODEL = "phi3"

DB_CONFIG = {
    'host': "localhost",
    'user': "root",
    'password': "",
    'database': "med_buddy_db"
}

# Force UTF-8 encoding for Windows console
if sys.platform.startswith('win'):
    sys.stdout.reconfigure(encoding='utf-8')

# Optional Gemini Support (can be set via env or config)
GEMINI_API_KEY = os.environ.get("GEMINI_API_KEY", "")

# -----------------------------------------------

def get_report_details(file_path):
    conn = mysql.connector.connect(**DB_CONFIG)
    cursor = conn.cursor(dictionary=True, buffered=True)
    try:
        query = "SELECT * FROM patient_images WHERE file_path = %s"
        cursor.execute(query, (file_path,))
        return cursor.fetchone()
    except Exception as e:
        sys.stderr.write(f"DB Error: {str(e)}\n")
        return None
    finally:
        if conn.is_connected():
            cursor.close()
            conn.close()

def summarize_with_gemini(data, file_path):
    # This is a placeholder for when google-generativeai is available and key is provided
    try:
        import google.generativeai as genai
        genai.configure(api_key=GEMINI_API_KEY)
        model = genai.GenerativeModel('gemini-1.5-flash')
        
        # In a real setup, we'd load the image from disk
        # full_path = os.path.join(os.path.dirname(__DIR__), 'uploads', file_path)
        
        prompt = f"""
        Analyze this medical report for patient {data.get('full_name')} ({data.get('gender')}).
        Known conditions: {data.get('chronic_conditions')}
        Allergies: {data.get('allergies')}
        
        Focus on:
        - Patient condition
        - Important findings
        - Diagnosis
        - Recommended treatment or action
        
        Provide clear bullet points.
        """
        
        # For now, if no image loading logic, we just use text part of Gemini
        response = model.generate_content(prompt)
        print(response.text)
        return True
    except Exception as e:
        return False

def summarize_report(data):
    if not data:
        return "No report data found to summarize."

    # If we have Gemini Key, try Vision first (logic simplified here)
    if GEMINI_API_KEY and summarize_with_gemini(data, data.get('file_path')):
        return

    # Fallback to Ollama (Llama 3) with speed optimizations
    prompt = f"""
Medical Report Analysis (CBC). 
Extract EXPLICIT data only. 
Rules: No guessing, no inferences, no personal data, no signatures.

Format:
Test Type: (e.g. Complete Blood Count)
Key Findings: (Hemoglobin, WBC, Diff count)
RBC Indices: (HCT, RBC, MCV, MCH, MCHC, RDW)
Platelet Indices: (Count, MPV, PDW, PCT)
Morphology: (RBC, WBC, Platelets)
Abnormal Results: (Only values outside range or "None detected")

Report Data:
Description: {data.get('description', 'N/A')}
Type: {data.get('image_type', 'Medical Record')}
Metadata: {data.get('modality', 'N/A')}
"""

    try:
        response = requests.post(
            OLLAMA_API_URL,
            json={
                "model": OLLAMA_MODEL,
                "prompt": prompt,
                "system": "You are a senior medical consultant. Provide extremely concise, structured summaries. No small talk.",
                "stream": True,
                "options": {
                    "temperature": 0,
                    "num_ctx": 1024,
                    "num_predict": 512
                }
            }
        )
        
        full_content = ""
        for line in response.iter_lines():
            if line:
                chunk = json.loads(line)
                content = chunk.get("response", "")
                print(content, end='', flush=True)
                full_content += content
                if chunk.get("done"):
                    break
        
        return full_content
    except Exception as e:
        print(f"Error connecting to AI: {str(e)}")

if __name__ == "__main__":
    if len(sys.argv) < 2:
        sys.exit(1)
    
    path = sys.argv[1].strip()
    
    report_data = get_report_details(path)
    if report_data:
        summarize_report(report_data)
    else:
        # Extreme fallback if join fails or record missing
        fallback_data = {
            "image_type": "Medical Record",
            "description": f"File path: {path}. Patient record linkage issue."
        }
        summarize_report(fallback_data)
