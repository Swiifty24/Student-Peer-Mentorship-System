<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tutor KPI Dashboard</title>
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
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-left h1 {
            color: #667eea;
            font-size: 2.5em;
            margin-bottom: 5px;
        }

        .header-left p {
            color: #666;
            font-size: 1.1em;
        }

        .tutor-info {
            text-align: right;
        }

        .tutor-name {
            font-size: 1.2em;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .tutor-rate {
            color: #667eea;
            font-weight: 600;
        }

        .time-period {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .time-period select {
            padding: 10px 20px;
            font-size: 1em;
            border: 2px solid #667eea;
            border-radius: 8px;
            cursor: pointer;
            background: white;
            color: #667eea;
            font-weight: 600;
        }

        .refresh-btn {
            padding: 10px 25px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: transform 0.2s;
        }

        .refresh-btn:hover {
            transform: scale(1.05);
        }

        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .kpi-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .kpi-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.25);
        }

        .kpi-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }

        .kpi-icon {
            font-size: 2.5em;
            margin-bottom: 15px;
        }

        .kpi-title {
            color: #666;
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .kpi-value {
            font-size: 2.5em;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }

        .kpi-subtitle {
            font-size: 0.85em;
            color: #888;
            margin-top: 5px;
        }

        .students-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }

        .students-section h2 {
            color: #667eea;
            margin-bottom: 20px;
            font-size: 1.8em;
        }

        .student-list {
            display: grid;
            gap: 15px;
        }

        .student-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #667eea;
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 20px;
            align-items: center;
            transition: transform 0.2s;
        }

        .student-card:hover {
            transform: translateX(5px);
            background: #e9ecef;
        }

        .student-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5em;
            font-weight: bold;
        }

        .student-info h3 {
            color: #333;
            margin-bottom: 5px;
        }

        .student-info p {
            color: #666;
            font-size: 0.9em;
        }

        .student-stats {
            text-align: right;
        }

        .stat-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
            margin: 2px;
        }

        .badge-sessions {
            background: #d4edda;
            color: #155724;
        }

        .badge-status {
            background: #cce5ff;
            color: #004085;
        }

        .chart-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }

        .chart-section h2 {
            color: #667eea;
            margin-bottom: 20px;
            font-size: 1.8em;
        }

        .chart-container {
            height: 300px;
            position: relative;
        }

        .bar-chart {
            display: flex;
            align-items: flex-end;
            justify-content: space-around;
            height: 100%;
            padding: 20px 0;
        }

        .bar {
            flex: 1;
            margin: 0 10px;
            background: linear-gradient(180deg, #667eea, #764ba2);
            border-radius: 8px 8px 0 0;
            position: relative;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .bar:hover {
            opacity: 0.8;
            transform: scaleY(1.05);
        }

        .bar-label {
            text-align: center;
            margin-top: 10px;
            font-size: 0.9em;
            color: #666;
            font-weight: 600;
        }

        .bar-value {
            position: absolute;
            top: -25px;
            width: 100%;
            text-align: center;
            font-weight: bold;
            color: #667eea;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: #667eea;
            font-size: 1.2em;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                text-align: center;
            }

            .tutor-info {
                text-align: center;
                margin-top: 15px;
            }

            .header-left h1 {
                font-size: 1.8em;
            }

            .kpi-grid {
                grid-template-columns: 1fr;
            }

            .student-card {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .student-avatar {
                margin: 0 auto;
            }

            .student-stats {
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-left">
                <h1>📊 Tutor Performance Dashboard</h1>
                <p>Track your mentorship impact and student progress</p>
            </div>
            <div class="tutor-info">
                <div class="tutor-name" id="tutorName">Loading...</div>
                <div class="tutor-rate" id="tutorRate">$0.00/hr</div>
            </div>
        </div>

        <div class="time-period">
            <div>
                <label for="period" style="margin-right: 15px; font-weight: 600; color: #667eea;">Time Period:</label>
                <select id="period" onchange="loadDashboardData()">
                    <option value="week">This Week</option>
                    <option value="month" selected>This Month</option>
                    <option value="quarter">This Quarter</option>
                    <option value="all">All Time</option>
                </select>
            </div>
            <button class="refresh-btn" onclick="loadDashboardData()">🔄 Refresh Data</button>
        </div>

        <div id="kpiSection" class="kpi-grid">
            <!-- KPI cards will be loaded here -->
        </div>

        <div class="students-section">
            <h2>👥 My Students</h2>
            <div id="studentList" class="student-list">
                <div class="loading">Loading students...</div>
            </div>
        </div>

        <div class="chart-section">
            <h2>📈 Session Activity Overview</h2>
            <div class="chart-container">
                <div class="bar-chart" id="barChart">
                    <!-- Bars will be generated dynamically -->
                </div>
            </div>
        </div>
    </div>

    <script>
        // Get tutor ID from session/URL (modify as needed for your auth system)
        const tutorUserId = getCurrentTutorId(); // You'll need to implement this
        
        function getCurrentTutorId() {
            // This should get the current logged-in tutor's ID
            // For now, return a placeholder - replace with your auth logic
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get('tutorId') || sessionStorage.getItem('tutorUserId') || 1;
        }

        async function loadDashboardData() {
            try {
                // Load tutor profile
                await loadTutorProfile();
                
                // Load KPIs
                await loadKPIs();
                
                // Load students
                await loadStudents();
                
                // Load chart data
                await loadChartData();
            } catch (error) {
                console.error('Error loading dashboard:', error);
            }
        }

        async function loadTutorProfile() {
            try {
                // Replace with your actual API endpoint
                const response = await fetch(`/api/tutorprofiles.php?userId=${tutorUserId}`);
                const tutor = await response.json();
                
                document.getElementById('tutorName').textContent = tutor.firstName + ' ' + tutor.lastName;
                document.getElementById('tutorRate').textContent = '$' + tutor.hourlyRate + '/hr';
            } catch (error) {
                console.error('Error loading tutor profile:', error);
                // Fallback for demo
                document.getElementById('tutorName').textContent = 'Tutor Name';
                document.getElementById('tutorRate').textContent = '$25.00/hr';
            }
        }

        async function loadKPIs() {
            try {
                // Replace with your actual API endpoint
                const response = await fetch(`/api/kpi.php?tutorId=${tutorUserId}`);
                const kpiData = await response.json();
                
                displayKPIs(kpiData);
            } catch (error) {
                console.error('Error loading KPIs:', error);
                // Display demo data
                displayKPIs({
                    activeStudents: 12,
                    totalSessions: 45,
                    totalHours: 67.5,
                    averageRating: 4.8,
                    completionRate: 92,
                    totalEarnings: 1687.50
                });
            }
        }

        function displayKPIs(data) {
            const kpiSection = document.getElementById('kpiSection');
            kpiSection.innerHTML = `
                <div class="kpi-card">
                    <div class="kpi-icon">👥</div>
                    <div class="kpi-title">Active Students</div>
                    <div class="kpi-value">${data.activeStudents || 0}</div>
                    <div class="kpi-subtitle">Currently enrolled</div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-icon">📅</div>
                    <div class="kpi-title">Total Sessions</div>
                    <div class="kpi-value">${data.totalSessions || 0}</div>
                    <div class="kpi-subtitle">Completed this period</div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-icon">⏱️</div>
                    <div class="kpi-title">Total Hours</div>
                    <div class="kpi-value">${data.totalHours || 0}</div>
                    <div class="kpi-subtitle">Teaching time</div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-icon">⭐</div>
                    <div class="kpi-title">Average Rating</div>
                    <div class="kpi-value">${data.averageRating || 0}</div>
                    <div class="kpi-subtitle">Student feedback</div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-icon">✅</div>
                    <div class="kpi-title">Completion Rate</div>
                    <div class="kpi-value">${data.completionRate || 0}%</div>
                    <div class="kpi-subtitle">Sessions completed</div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-icon">💰</div>
                    <div class="kpi-title">Total Earnings</div>
                    <div class="kpi-value">$${data.totalEarnings || 0}</div>
                    <div class="kpi-subtitle">This period</div>
                </div>
            `;
        }

        async function loadStudents() {
            try {
                // Replace with your actual API endpoint
                const response = await fetch(`/api/enrollments.php?tutorId=${tutorUserId}`);
                const students = await response.json();
                
                displayStudents(students);
            } catch (error) {
                console.error('Error loading students:', error);
                // Display demo data
                displayStudents([
                    { id: 1, name: 'John Doe', email: 'john@example.com', sessions: 8, status: 'active' },
                    { id: 2, name: 'Jane Smith', email: 'jane@example.com', sessions: 12, status: 'active' },
                    { id: 3, name: 'Mike Johnson', email: 'mike@example.com', sessions: 5, status: 'pending' }
                ]);
            }
        }

        function displayStudents(students) {
            const studentList = document.getElementById('studentList');
            
            if (!students || students.length === 0) {
                studentList.innerHTML = '<p style="text-align: center; color: #666;">No students enrolled yet.</p>';
                return;
            }

            studentList.innerHTML = students.map(student => {
                const initials = student.name.split(' ').map(n => n[0]).join('');
                return `
                    <div class="student-card">
                        <div class="student-avatar">${initials}</div>
                        <div class="student-info">
                            <h3>${student.name}</h3>
                            <p>${student.email}</p>
                        </div>
                        <div class="student-stats">
                            <span class="stat-badge badge-sessions">${student.sessions || 0} sessions</span>
                            <span class="stat-badge badge-status">${student.status || 'active'}</span>
                        </div>
                    </div>
                `;
            }).join('');
        }

        async function loadChartData() {
            try {
                // Replace with your actual API endpoint
                const response = await fetch(`/api/sessions-chart.php?tutorId=${tutorUserId}`);
                const chartData = await response.json();
                
                createBarChart(chartData.values, chartData.labels);
            } catch (error) {
                console.error('Error loading chart data:', error);
                // Display demo data
                createBarChart([5, 8, 12, 9, 11], ['Week 1', 'Week 2', 'Week 3', 'Week 4', 'Week 5']);
            }
        }

        function createBarChart(values, labels) {
            const barChart = document.getElementById('barChart');
            barChart.innerHTML = '';
            
            const maxValue = Math.max(...values);
            
            values.forEach((value, index) => {
                const barContainer = document.createElement('div');
                barContainer.style.flex = '1';
                barContainer.style.display = 'flex';
                barContainer.style.flexDirection = 'column';
                barContainer.style.alignItems = 'center';
                
                const barWrapper = document.createElement('div');
                barWrapper.style.width = '100%';
                barWrapper.style.height = '250px';
                barWrapper.style.display = 'flex';
                barWrapper.style.alignItems = 'flex-end';
                barWrapper.style.justifyContent = 'center';
                barWrapper.style.position = 'relative';
                
                const bar = document.createElement('div');
                const height = (value / maxValue) * 100;
                bar.className = 'bar';
                bar.style.height = height + '%';
                bar.style.width = '80%';
                
                const barValue = document.createElement('div');
                barValue.className = 'bar-value';
                barValue.textContent = value;
                
                bar.appendChild(barValue);
                barWrapper.appendChild(bar);
                
                const label = document.createElement('div');
                label.className = 'bar-label';
                label.textContent = labels[index];
                
                barContainer.appendChild(barWrapper);
                barContainer.appendChild(label);
                barChart.appendChild(barContainer);
            });
        }

        // Load dashboard on page load
        window.onload = function() {
            loadDashboardData();
        };
    </script>
</body>
</html>