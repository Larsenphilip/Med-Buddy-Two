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
    <title>Patient Intelligence - Med Buddy</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Using Remix Icon -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">

    <style>
        :root {
            --primary-color: #0066CC;
            --primary-hover: #004C99;
            --secondary-color: #E6F4FF;
            --accent-color: #00D2D3;
            --text-dark: #1F2937;
            --text-light: #6B7280;
            --white: #FFFFFF;
            --card-bg: #FFFFFF;
            --border-color: #E5E7EB;
            --sidebar-width: 260px;
            --header-height: 70px;
            --dashboard-bg: #F8FAFC;
            --border-radius: 12px;
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: var(--dashboard-bg);
            color: var(--text-dark);
            height: 100vh;
            display: flex;
            overflow: hidden;
        }

        /* --- Sidebar --- */
        .sidebar {
            width: var(--sidebar-width);
            background-color: var(--secondary-color);
            height: 100vh;
            display: flex;
            flex-direction: column;
            border-right: 1px solid var(--border-color);
            padding: 1.5rem;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 100;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary-color);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            letter-spacing: -0.5px;
            margin-bottom: 3rem;
            padding-left: 0.5rem;
        }

        .sidebar-menu {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .sidebar-menu a {
            text-decoration: none;
            color: var(--text-light);
            font-weight: 500;
            padding: 0.85rem 1rem;
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: var(--transition);
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background-color: var(--primary-color);
            color: var(--white);
            box-shadow: 0 4px 12px rgba(0, 102, 204, 0.2);
        }

        .logout-btn {
            margin-top: auto;
            color: #ef4444 !important;
        }

        .logout-btn:hover {
            background-color: #fee2e2 !important;
            color: #ef4444 !important;
            box-shadow: none !important;
        }

        /* --- Main Content --- */
        .main-content {
            margin-left: var(--sidebar-width);
            flex: 1;
            height: 100vh;
            overflow-y: auto;
            position: relative;
        }

        /* --- Header --- */
        .top-bar {
            height: var(--header-height);
            background-color: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            display: flex;
            justify-content: flex-end;
            align-items: center;
            padding: 0 2rem;
            position: sticky;
            top: 0;
            z-index: 90;
            border-bottom: 1px solid var(--border-color);
        }

        .top-bar-info {
            display: flex;
            align-items: center;
            gap: 2rem;
            font-size: 0.9rem;
            color: var(--text-light);
        }

        /* --- Content Container --- */
        .dashboard-container {
            padding: 2rem;
            max-width: 1000px;
            margin: 0 auto;
        }

        .page-header {
            margin-bottom: 2rem;
            text-align: center;
        }

        .page-header h1 {
            font-size: 2rem;
            font-weight: 800;
            color: var(--text-dark);
            letter-spacing: -1px;
            margin-bottom: 0.5rem;
        }

        .page-header p {
            color: var(--text-light);
            font-size: 1.1rem;
        }

        .search-card {
            background: var(--white);
            border-radius: 20px;
            padding: 3rem;
            margin-bottom: 2rem;
            border: 1px solid var(--border-color);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        .search-form {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .search-input-wrapper {
            flex: 1;
            position: relative;
        }

        .search-input-wrapper i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            font-size: 1.2rem;
        }

        .search-input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 500;
            outline: none;
            transition: var(--transition);
        }

        .search-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(0, 102, 204, 0.1);
        }

        .btn-analyze {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 0 2rem;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-analyze:hover {
            background-color: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 102, 204, 0.2);
        }

        .btn-analyze:disabled {
            background-color: #cbd5e1;
            cursor: not-allowed;
            transform: none;
        }

        /* --- Analysis Results --- */
        .results-container {
            display: none;
            background: #f8fafc;
            border-radius: 16px;
            padding: 2rem;
            border: 1px solid #e2e8f0;
            margin-top: 2rem;
            position: relative;
        }

        .results-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e2e8f0;
            color: var(--primary-color);
            font-weight: 700;
            font-size: 1.1rem;
        }

        .summary-body {
            line-height: 1.7;
            color: #334155;
            font-size: 1rem;
            white-space: pre-wrap;
        }

        .summary-body strong {
            color: var(--text-dark);
            font-weight: 700;
        }

        .placeholder-area {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-light);
            background: #f9fafb;
            border: 2px dashed var(--border-color);
            border-radius: 16px;
        }

        .placeholder-area i {
            font-size: 3rem;
            color: var(--border-color);
            margin-bottom: 1rem;
            display: block;
        }

        /* Loading Spinner */
        .spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Report Summary Styling */
        .report-summary-box {
            margin-top: 1rem;
            padding: 1.25rem;
            background: var(--white);
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            font-size: 0.9rem;
            color: #475569;
        }

        .btn-summarize-report {
            background: var(--secondary-color);
            color: var(--primary-color);
            border: 1px solid rgba(0, 102, 204, 0.2);
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 700;
            cursor: pointer;
            transition: var(--transition);
            margin-top: 0.5rem;
        }

        .btn-summarize-report:hover {
            background: var(--primary-color);
            color: var(--white);
        }

        /* --- Scrollbar --- */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <aside class="sidebar">
        <a href="index.php" class="logo">
            <i class="ri-heart-pulse-fill"></i>
            <span>Med Buddy</span>
        </a>

        <ul class="sidebar-menu">
            <li><a href="index.php"><i class="ri-calendar-check-line"></i> Dashboard</a></li>
            <li><a href="search_patient.php" class="active"><i class="ri-folder-user-line"></i> Patient Results</a></li>
            <li><a href="logout.php" class="logout-btn"><i class="ri-logout-box-line"></i> Logout</a></li>
        </ul>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Top Header -->
        <div class="top-bar">
            <div class="top-bar-info">
                <div class="user-profile">
                    <i class="ri-user-3-line"></i>
                    <span>Doctor Portal | Dr. <?php echo htmlspecialchars(explode(' ', $doctor_name)[0]); ?></span>
                </div>
            </div>
        </div>

        <div class="dashboard-container">
            <div class="page-header">
                <h1>Patient Intelligence System</h1>
                <p>AI-powered medical record analysis and summarization</p>
            </div>

            <div class="search-card">
                <form id="searchForm" class="search-form">
                    <div class="search-input-wrapper">
                        <i class="ri-search-line"></i>
                        <input type="text" id="patient_id" name="patient_id" class="search-input" placeholder="Enter Patient ID (e.g. PAT-202X-XXX)" required>
                    </div>
                    <button type="submit" class="btn-analyze">
                        <span class="spinner" id="loader"></span>
                        <span id="btnText"><i class="ri-magic-line"></i> Analyze History</span>
                    </button>
                </form>

                <div id="resultContent" class="results-container">
                    <div class="results-header">
                        <i class="ri-robot-line"></i>
                        AI Generated Case Summary
                    </div>
                    <div id="summary-text" class="summary-body"></div>
                </div>

                <div id="placeholderArea" class="placeholder-area">
                    <i class="ri-id-card-line"></i>
                    <p>Enter a unique Patient ID to generate a secure medical overview powered by Gemini AI.</p>
                </div>
            </div>
        </div>

        <?php include '../footer.php'; ?>
    </main>

    <script>
        const searchForm = document.getElementById('searchForm');
        const resultContent = document.getElementById('resultContent');
        const placeholderArea = document.getElementById('placeholderArea');
        const summaryText = document.getElementById('summary-text');
        const loader = document.getElementById('loader');
        const btnText = document.getElementById('btnText');
        const searchBtn = searchForm.querySelector('button');

        function formatMarkdown(text) {
            let html = text
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;");

            // Bold **text**
            html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
            
            // Italic *text*
            html = html.replace(/\*(.*?)\*/g, '<em>$1</em>');
            
            // Links [text](url)
            html = html.replace(/\[(.*?)\]\((.*?)\)/g, (match, text, url) => {
                let reportLink = `<a href="${url}" target="_blank" style="color: #0066CC; text-decoration: underline; font-weight: 700;">${text}</a>`;
                
                if (text.toLowerCase().includes('report') || text.toLowerCase().includes('file')) {
                    let filePath = url.split('/uploads/')[1] || '';
                    if (filePath) {
                        return `${reportLink}<br><button class="btn-summarize-report" data-path="${filePath}"><i class="ri-brain-line"></i> Summarize This Report</button><div class="report-summary-box" id="summary-${filePath.replace(/[^a-zA-Z0-9]/g, '_')}" style="display: none;"></div>`;
                    }
                }
                return reportLink;
            });
            
            html = html.replace(/\n/g, '<br>');
            return html;
        }

        // Handle summarization buttons
        document.addEventListener('click', async (e) => {
            if (e.target && (e.target.classList.contains('btn-summarize-report') || e.target.parentElement.classList.contains('btn-summarize-report'))) {
                const btn = e.target.classList.contains('btn-summarize-report') ? e.target : e.target.parentElement;
                const path = btn.getAttribute('data-path');
                const summaryId = `summary-${path.replace(/[^a-zA-Z0-9]/g, '_')}`;
                const summaryBox = document.getElementById(summaryId);
                
                if (summaryBox.style.display === 'block') {
                    summaryBox.style.display = 'none';
                    btn.innerHTML = '<i class="ri-brain-line"></i> Summarize This Report';
                    return;
                }

                btn.disabled = true;
                btn.innerHTML = '<i class="ri-loader-4-line ri-spin"></i> Analyzing...';
                summaryBox.innerHTML = '<em>Consulting AI...</em>';
                summaryBox.style.display = 'block';

                let fullText = '';
                try {
                    const response = await fetch(`summarize_report_action.php?file_path=${encodeURIComponent(path)}`);
                    if (!response.ok) throw new Error('API Error');

                    const reader = response.body.getReader();
                    const decoder = new TextDecoder();
                    summaryBox.innerHTML = '';

                    while (true) {
                        const { value, done } = await reader.read();
                        if (done) break;
                        const chunk = decoder.decode(value, { stream: true });
                        fullText += chunk;
                        summaryBox.innerHTML = fullText.replace(/\n/g, '<br>').replace(/\* /g, '• ');
                    }
                } catch (error) {
                    summaryBox.innerHTML = `<span style="color: #ef4444;">Error: ${error.message}</span>`;
                } finally {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="ri-eye-off-line"></i> Hide Summary';
                }
            }
        });

        searchForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const patientId = document.getElementById('patient_id').value;
            
            summaryText.innerHTML = '';
            placeholderArea.style.display = 'none';
            resultContent.style.display = 'block';
            loader.style.display = 'block';
            btnText.style.display = 'none';
            searchBtn.disabled = true;

            let fullText = '';
            try {
                const response = await fetch(`generate_stream.php?patient_id=${encodeURIComponent(patientId)}`);
                if (!response.ok) throw new Error('System Connection Error');

                const reader = response.body.getReader();
                const decoder = new TextDecoder();

                while (true) {
                    const { value, done } = await reader.read();
                    if (done) break;
                    const chunk = decoder.decode(value, { stream: true });
                    fullText += chunk;
                    summaryText.innerHTML = formatMarkdown(fullText);
                    resultContent.scrollIntoView({ behavior: 'smooth', block: 'end' });
                }
            } catch (error) {
                summaryText.innerHTML = `<span style="color: #ef4444;">Error: ${error.message}</span>`;
            } finally {
                loader.style.display = 'none';
                btnText.style.display = 'inline';
                searchBtn.disabled = false;
            }
        });

        // Sidebar active state logic
        document.querySelectorAll('.sidebar-menu a').forEach(link => {
            link.addEventListener('click', function() {
                document.querySelectorAll('.sidebar-menu a').forEach(l => l.classList.remove('active'));
                this.classList.add('active');
            });
        });
    </script>

</body>
</html>

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
