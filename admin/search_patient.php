<?php
session_start();

// Check if doctor is logged in
if (!isset($_SESSION['doctor_id'])) {
    header("Location: ../doctor_login.php");
    exit;
}

include '../db_config.php';
$doctor_id = $_SESSION['doctor_id'];
$doctor_name = $_SESSION['doctor_name'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Patient - Med Buddy</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #0066CC;
            --secondary-color: #f4fdf0;
            --white: #ffffff;
            --text-dark: #1F2937;
            --border-color: #e5e7eb;
        }

        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            background-color: #f9fafb;
            display: flex;
            height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 250px;
            background-color: var(--white);
            border-right: 1px solid var(--border-color);
            padding: 2rem;
            display: flex;
            flex-direction: column;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary-color);
            margin-bottom: 3rem;
        }

        .menu-item {
            padding: 0.75rem 1rem;
            color: var(--text-dark);
            text-decoration: none;
            border-radius: 6px;
            margin-bottom: 0.5rem;
            font-weight: 500;
            display: block;
        }

        .menu-item.active {
            background-color: var(--secondary-color);
            color: var(--primary-color);
        }

        .menu-item:hover {
            background-color: #f3f4f6;
        }

        .logout {
            margin-top: auto;
            color: red;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            padding: 2rem;
            overflow-y: auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .card {
            background: var(--white);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        h2 {
            margin-top: 0;
            color: var(--text-dark);
            margin-bottom: 1.5rem;
        }

        /* Search Form Styles */
        .search-container {
            max-width: 600px;
            margin: 0 auto;
        }

        .search-box {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .search-input {
            flex: 1;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 1rem;
            outline: none;
            transition: border-color 0.2s;
        }

        .search-input:focus {
            border-color: var(--primary-color);
        }

        .btn-search {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-search:hover {
            background-color: #0052a3;
        }

        .btn-search:disabled {
            background-color: #9ca3af;
            cursor: not-allowed;
        }

        /* AI Summary Placeholder */
        .ai-result-area {
            border: 2px dashed var(--border-color);
            border-radius: 8px;
            padding: 3rem;
            text-align: center;
            color: #6b7280;
            background-color: #f9fafb;
        }

        .result-content {
            text-align: left;
            padding: 1.5rem;
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 6px;
            color: #0c4a6e;
            display: none;
            white-space: pre-wrap;
            font-size: 0.95rem;
            line-height: 1.6;
        }

        .result-content h3 {
            margin-top: 0;
            margin-bottom: 1rem;
            color: #0369a1;
            border-bottom: 1px solid #bae6fd;
            padding-bottom: 0.5rem;
        }

        #summary-text {
            color: #1e293b;
        }

        /* Loading Spinner */
        .spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="logo">Med Buddy</div>
        <a href="index.php" class="menu-item">Dashboard</a>
        <a href="search_patient.php" class="menu-item active">Patient Search</a>
        <a href="logout.php" class="menu-item logout">Logout</a>
    </div>

    <div class="main-content">
        <div class="header">
            <h1>Patient Intelligence System</h1>
            <div>Doctor: <strong><?php echo htmlspecialchars($doctor_name); ?></strong></div>
        </div>

        <div class="card">
            <div class="search-container">
                <div style="text-align: center; margin-bottom: 2rem;">
                    <h2>Find Patient Records</h2>
                    <p style="color: #666;">Enter a Patient ID to generate an AI-powered summary of their medical history.</p>
                </div>

                <form id="searchForm" class="search-box">
                    <input type="text" id="patient_id" name="patient_id" class="search-input" placeholder="Enter Patient ID (e.g., PAT-2023-001)" required>
                    <button type="submit" class="btn-search">
                        <span class="spinner" id="loader"></span>
                        <span id="btnText">Search & Analyze</span>
                    </button>
                </form>

                <div id="resultContent" class="result-content">
                    <h3>Analysis Result</h3>
                    <div id="summary-text"></div>
                </div>

                <div id="placeholderArea" class="ai-result-area">
                    <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin-bottom: 1rem; color: #9ca3af;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                    </svg>
                    <p>Patient data and AI summary will appear here after search.</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        const searchForm = document.getElementById('searchForm');
        const resultContent = document.getElementById('resultContent');
        const placeholderArea = document.getElementById('placeholderArea');
        const summaryText = document.getElementById('summary-text');
        const loader = document.getElementById('loader');
        const btnText = document.getElementById('btnText');
        const searchBtn = searchForm.querySelector('button');

        function formatMarkdown(text) {
            // Escape HTML
            let html = text
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");

            // Bold **text**
            html = html.replace(/\*\*(.*?)\*\*/g, '<strong style="color: #1F2937;">$1</strong>');
            
            // Italic *text*
            html = html.replace(/\*(.*?)\*/g, '<em>$1</em>');
            
            // Links [text](url)
            html = html.replace(/\[(.*?)\]\((.*?)\)/g, (match, text, url) => {
                let reportLink = `<a href="${url}" target="_blank" style="color: #0066CC; text-decoration: underline; font-weight: 500;">${text}</a>`;
                
                // If it's a medical report, add a summarize button
                if (text.toLowerCase().includes('report')) {
                    // Extract the relative path from the URL
                    // Example URL: http://localhost/Med-Buddy/uploads/reports/48.jpg
                    let filePath = url.split('/uploads/')[1] || '';
                    if (filePath) {
                        return `${reportLink}<br><button class="btn-summarize-report" data-path="${filePath}" style="margin-top: 5px; background: #0066CC; color: white; border: none; padding: 4px 10px; border-radius: 4px; font-size: 0.8rem; cursor: pointer;">Summarize Report</button><div class="report-summary-box" id="summary-${filePath.replace(/[^a-zA-Z0-9]/g, '_')}" style="margin-top: 8px; padding: 10px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; display: none; font-size: 0.85rem; color: #334155;"></div>`;
                    }
                }
                return reportLink;
            });
            
            // Newlines
            html = html.replace(/\n/g, '<br>');
            
            return html;
        }

        // Global handler for summarize buttons (using delegation)
        document.addEventListener('click', async (e) => {
            if (e.target && e.target.classList.contains('btn-summarize-report')) {
                const btn = e.target;
                const path = btn.getAttribute('data-path');
                const summaryId = `summary-${path.replace(/[^a-zA-Z0-9]/g, '_')}`;
                const summaryBox = document.getElementById(summaryId);
                
                if (summaryBox.style.display === 'block') {
                    summaryBox.style.display = 'none';
                    btn.textContent = 'Summarize Report';
                    return;
                }

                // Show loading state
                btn.disabled = true;
                btn.textContent = 'Summarizing...';
                summaryBox.innerHTML = '<em>Analyzing report...</em>';
                summaryBox.style.display = 'block';

                let fullText = '';
                try {
                    const response = await fetch(`summarize_report_action.php?file_path=${encodeURIComponent(path)}`);
                    if (!response.ok) throw new Error('Failed to connect to summarization service');

                    const reader = response.body.getReader();
                    const decoder = new TextDecoder();

                    summaryBox.innerHTML = ''; // Clear loading text

                    while (true) {
                        const { value, done } = await reader.read();
                        if (done) break;
                        
                        const chunk = decoder.decode(value, { stream: true });
                        fullText += chunk;
                        
                        // Update box as text arrives, simple formatting for bullets
                        summaryBox.innerHTML = fullText.replace(/\n/g, '<br>').replace(/\* /g, '• ');
                    }
                } catch (error) {
                    summaryBox.innerHTML = `<span style="color: #ef4444;">Error: ${error.message}</span>`;
                } finally {
                    btn.disabled = false;
                    btn.textContent = 'Hide Summary';
                }
            }
        });

        searchForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const patientId = document.getElementById('patient_id').value;
            
            // Reset UI
            summaryText.innerHTML = '';
            placeholderArea.style.display = 'none';
            resultContent.style.display = 'block';
            loader.style.display = 'block';
            btnText.textContent = 'Analyzing...';
            searchBtn.disabled = true;

            let fullText = '';

            try {
                const response = await fetch(`generate_stream.php?patient_id=${encodeURIComponent(patientId)}`);
                
                if (!response.ok) throw new Error('Failed to connect to AI system');

                const reader = response.body.getReader();
                const decoder = new TextDecoder();

                while (true) {
                    const { value, done } = await reader.read();
                    if (done) break;
                    
                    const chunk = decoder.decode(value, { stream: true });
                    fullText += chunk;
                    
                    // Update UI as text arrives
                    summaryText.innerHTML = formatMarkdown(fullText);
                    
                    // Auto-scroll to bottom of card if needed
                    resultContent.scrollIntoView({ behavior: 'smooth', block: 'end' });
                }
            } catch (error) {
                summaryText.innerHTML = `<span style="color: #ef4444;">Error: ${error.message}</span>`;
            } finally {
                loader.style.display = 'none';
                btnText.textContent = 'Search & Analyze';
                searchBtn.disabled = false;
            }
        });
    </script>
</body>
</html>
