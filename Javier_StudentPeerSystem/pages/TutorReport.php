<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - Tutor Portal</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .header h1 {
            color: #667eea;
            font-size: 2.5em;
            margin-bottom: 10px;
        }

        .header p {
            color: #666;
            font-size: 1.1em;
        }

        .report-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .report-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            transition: all 0.3s;
            cursor: pointer;
        }

        .report-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.25);
        }

        .report-icon {
            font-size: 3em;
            margin-bottom: 15px;
        }

        .report-title {
            font-size: 1.5em;
            color: #333;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .report-description {
            color: #666;
            font-size: 0.95em;
            margin-bottom: 20px;
            line-height: 1.5;
        }

        .report-actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            font-size: 0.9em;
        }

        .btn-generate {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            flex: 1;
        }

        .btn-generate:hover {
            transform: scale(1.05);
        }

        .btn-download {
            background: #28a745;
            color: white;
            padding: 10px 15px;
        }

        .btn-download:hover {
            background: #218838;
        }

        .filters-section {
            background: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }

        .filters-section h2 {
            color: #667eea;
            margin-bottom: 20px;
            font-size: 1.5em;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .filter-group label {
            color: #666;
            font-size: 0.9em;
            font-weight: 600;
        }

        .filter-group select,
        .filter-group input {
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1em;
        }

        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: #667eea;
        }

        .report-preview {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            margin-bottom: 30px;
        }

        .report-preview h2 {
            color: #667eea;
            margin-bottom: 20px;
            font-size: 1.8em;
        }

        .preview-content {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .summary-item {
            background: white;
            padding: 15px;
            border-radius: 8px;
        }

        .summary-item h4 {
            color: #666;
            font-size: 0.85em;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .summary-item p {
            color: #333;
            font-size: 1.8em;
            font-weight: bold;
        }

        .data-table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }

        .data-table th {
            background: #667eea;
            color: white;
            padding: 12px;
            text-align: left;
        }

        .data-table td {
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
        }

        .data-table tr:hover {
            background: #f8f9fa;
        }

        .export-section {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            padding: 40px;
            border-radius: 15px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-header h3 {
            color: #667eea;
            font-size: 1.8em;
        }

        .close-btn {
            font-size: 2em;
            cursor: pointer;
            color: #999;
        }

        .close-btn:hover {
            color: #333;
        }

        @media (max-width: 768px) {
            .report-grid {
                grid-template-columns: 1fr;
            }

            .filter-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Reports & Analytics</h1>
            <p>Generate comprehensive reports and insights</p>
        </div>

        <div class="filters-section">
            <h2>🔧 Report Filters</h2>
            <div class="filter-grid">
                <div class="filter-group">
                    <label>Date Range</label>
                    <select id="dateRange">
                        <option value="week">This Week</option>
                        <option value="month" selected>This Month</option>
                        <option value="quarter">This Quarter</option>
                        <option value="year">This Year</option>
                        <option value="custom">Custom Range</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Start Date</label>
                    <input type="date" id="startDate">
                </div>

                <div class="filter-group">
                    <label>End Date</label>
                    <input type="date" id="endDate">
                </div>

                <div class="filter-group">
                    <label>Course</label>
                    <select id="courseFilter">
                        <option value="all">All Courses</option>
                        <option value="math">Mathematics 101</option>
                        <option value="physics">Physics Advanced</option>
                        <option value="chemistry">Chemistry Basics</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="report-grid">
            <div class="report-card" onclick="generateReport('sessions')">
                <div class="report-icon">📅</div>
                <div class="report-title">Session Report</div>
                <div class="report-description">
                    Detailed breakdown of all tutoring sessions, attendance, duration, and completion rates.
                </div>
                <div class="report-actions">
                    <button class="btn btn-generate">Generate Report</button>
                    <button class="btn btn-download">📥</button>
                </div>
            </div>

            <div class="report-card" onclick="generateReport('students')">
                <div class="report-icon">👥</div>
                <div class="report-title">Student Progress Report</div>
                <div class="report-description">
                    Track individual student progress, performance metrics, and learning outcomes.
                </div>
                <div class="report-actions">
                    <button class="btn btn-generate">Generate Report</button>
                    <button class="btn btn-download">📥</button>
                </div>
            </div>

            <div class="report-card" onclick="generateReport('earnings')">
                <div class="report-icon">💰</div>
                <div class="report-title">Earnings Report</div>
                <div class="report-description">
                    Comprehensive financial summary including hours worked, rates, and total earnings.
                </div>
                <div class="report-actions">
                    <button class="btn btn-generate">Generate Report</button>
                    <button class="btn btn-download">📥</button>
                </div>
            </div>

            <div class="report-card" onclick="generateReport('performance')">
                <div class="report-icon">⭐</div>
                <div class="report-title">Performance Report</div>
                <div class="report-description">
                    Analysis of teaching effectiveness, student ratings, and feedback summary.
                </div>
                <div class="report-actions">
                    <button class="btn btn-generate">Generate Report</button>
                    <button class="btn btn-download">📥</button>
                </div>
            </div>

            <div class="report-card" onclick="generateReport('attendance')">
                <div class="report-icon">✅</div>
                <div class="report-title">Attendance Report</div>
                <div class="report-description">
                    Track student attendance patterns, no-shows, and scheduling efficiency.
                </div>
                <div class="report-actions">
                    <button class="btn btn-generate">Generate Report</button>
                    <button class="btn btn-download">📥</button>
                </div>
            </div>

            <div class="report-card" onclick="generateReport('courses')">
                <div class="report-icon">📚</div>
                <div class="report-title">Course Analytics</div>
                <div class="report-description">
                    Compare performance across different courses and subject areas.
                </div>
                <div class="report-actions">
                    <button class="btn btn-generate">Generate Report</button>
                    <button class="btn btn-download">📥</button>
                </div>
            </div>
        </div>

        <div class="report-preview" id="reportPreview" style="display: none;">
            <h2 id="previewTitle">Report Preview</h2>
            <div class="preview-content">
                <div class="summary-grid" id="summaryGrid">
                    <!-- Summary will be generated here -->
                </div>

                <table class="data-table" id="dataTable">
                    <!-- Table will be generated here -->
                </table>

                <div class="export-section">
                    <button class="btn btn-generate" onclick="exportPDF()">📄 Export as PDF</button>
                    <button class="btn btn-download" onclick="exportExcel()">📊 Export as Excel</button>
                    <button class="btn btn-download" onclick="exportCSV()">📋 Export as CSV</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal" id="reportModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Generating Report...</h3>
                <span class="close-btn" onclick="closeModal()">&times;</span>
            </div>
            <div id="modalBody">
                <p>Please wait while we generate your report...</p>
            </div>
        </div>
    </div>

    <script>
        const tutorUserId = getCurrentTutorId();

        function getCurrentTutorId() {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get('tutorId') || sessionStorage.getItem('tutorUserId') || 1;
        }

        async function generateReport(reportType) {
            showModal(`Generating ${reportType} report...`);

            try {
                // Replace with your actual API endpoint
                const response = await fetch(`/api/reports.php?type=${reportType}&tutorId=${tutorUserId}`);
                const reportData = await response.json();
                
                displayReportPreview(reportType, reportData);
                closeModal();
            } catch (error) {
                console.error('Error generating report:', error);
                // Generate demo data
                const demoData = generateDemoReportData(reportType);
                displayReportPreview(reportType, demoData);
                closeModal();
            }
        }

        function generateDemoReportData(reportType) {
            const baseData = {
                sessions: {
                    title: 'Session Report',
                    summary: {
                        'Total Sessions': 45,
                        'Completed': 42,
                        'Cancelled': 3,
                        'Avg Duration': '1.5 hrs'
                    },
                    tableHeaders: ['Date', 'Student', 'Course', 'Duration', 'Status'],
                    tableData: [
                        ['2024-11-15', 'John Doe', 'Math 101', '1.5 hrs', 'Completed'],
                        ['2024-11-14', 'Jane Smith', 'Physics', '2 hrs', 'Completed'],
                        ['2024-11-13', 'Mike Johnson', 'Chemistry', '1 hr', 'Cancelled']
                    ]
                },
                students: {
                    title: 'Student Progress Report',
                    summary: {
                        'Total Students': 12,
                        'Active': 10,
                        'Avg Rating': '4.8',
                        'Completion Rate': '92%'
                    },
                    tableHeaders: ['Student', 'Sessions', 'Progress', 'Rating', 'Status'],
                    tableData: [
                        ['John Doe', 12, '85%', '⭐⭐⭐⭐⭐', 'Active'],
                        ['Jane Smith', 8, '72%', '⭐⭐⭐⭐', 'Active'],
                        ['Mike Johnson', 15, '95%', '⭐⭐⭐⭐⭐', 'Active']
                    ]
                },
                earnings: {
                    title: 'Earnings Report',
                    summary: {
                        'Total Earnings': '$2,850',
                        'Total Hours': 95,
                        'Avg Rate': '$30/hr',
                        'Sessions': 45
                    },
                    tableHeaders: ['Date', 'Student', 'Hours', 'Rate', 'Amount'],
                    tableData: [
                        ['2024-11-15', 'John Doe', '1.5', '$30', '$45'],
                        ['2024-11-14', 'Jane Smith', '2.0', '$30', '$60'],
                        ['2024-11-13', 'Mike Johnson', '1.0', '$30', '$30']
                    ]
                },
                performance: {
                    title: 'Performance Report',
                    summary: {
                        'Avg Rating': '4.8',
                        'Response Time': '2.3 hrs',
                        'Completion Rate': '93%',
                        'Student Satisfaction': '96%'
                    },
                    tableHeaders: ['Metric', 'This Month', 'Last Month', 'Change'],
                    tableData: [
                        ['Avg Rating', '4.8', '4.6', '+0.2'],
                        ['Response Time', '2.3 hrs', '3.1 hrs', '-0.8 hrs'],
                        ['Sessions', '45', '38', '+7']
                    ]
                },
                attendance: {
                    title: 'Attendance Report',
                    summary: {
                        'Attendance Rate': '93%',
                        'Total Scheduled': 48,
                        'Completed': 45,
                        'No-Shows': 3
                    },
                    tableHeaders: ['Date', 'Student', 'Course', 'Status', 'Reason'],
                    tableData: [
                        ['2024-11-15', 'John Doe', 'Math 101', 'Present', '-'],
                        ['2024-11-14', 'Jane Smith', 'Physics', 'Present', '-'],
                        ['2024-11-13', 'Mike Johnson', 'Chemistry', 'Absent', 'Sick']
                    ]
                },
                courses: {
                    title: 'Course Analytics',
                    summary: {
                        'Total Courses': 5,
                        'Most Popular': 'Math 101',
                        'Highest Rating': 'Physics',
                        'Total Students': 12
                    },
                    tableHeaders: ['Course', 'Students', 'Sessions', 'Avg Rating', 'Revenue'],
                    tableData: [
                        ['Math 101', '5', '25', '4.9', '$750'],
                        ['Physics', '3', '12', '5.0', '$360'],
                        ['Chemistry', '4', '8', '4.5', '$240']
                    ]
                }
            };

            return baseData[reportType];
        }

        function displayReportPreview(reportType, data) {
            const preview = document.getElementById('reportPreview');
            const title = document.getElementById('previewTitle');
            const summaryGrid = document.getElementById('summaryGrid');
            const dataTable = document.getElementById('dataTable');

            title.textContent = data.title;

            // Generate summary
            summaryGrid.innerHTML = Object.entries(data.summary).map(([key, value]) => `
                <div class="summary-item">
                    <h4>${key}</h4>
                    <p>${value}</p>
                </div>
            `).join('');

            // Generate table
            const headers = data.tableHeaders.map(h => `<th>${h}</th>`).join('');
            const rows = data.tableData.map(row => 
                `<tr>${row.map(cell => `<td>${cell}</td>`).join('')}</tr>`
            ).join('');

            dataTable.innerHTML = `
                <thead><tr>${headers}</tr></thead>
                <tbody>${rows}</tbody>
            `;

            preview.style.display = 'block';
            preview.scrollIntoView({ behavior: 'smooth' });
        }

        function showModal(message) {
            const modal = document.getElementById('reportModal');
            const modalBody = document.getElementById('modalBody');
            modalBody.innerHTML = `<p>${message}</p>`;
            modal.classList.add('active');
        }

        function closeModal() {
            const modal = document.getElementById('reportModal');
            modal.classList.remove('active');
        }

        function exportPDF() {
            alert('Exporting report as PDF...');
            // Implement PDF export functionality
        }

        function exportExcel() {
            alert('Exporting report as Excel...');
            // Implement Excel export functionality
        }

        function exportCSV() {
            alert('Exporting report as CSV...');
            // Implement CSV export functionality
        }

        // Set default date range
        window.onload = function() {
            const today = new Date();
            const lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, today.getDate());
            
            document.getElementById('startDate').value = lastMonth.toISOString().split('T')[0];
            document.getElementById('endDate').value = today.toISOString().split('T')[0];
        };
    </script>
</body>
</html>