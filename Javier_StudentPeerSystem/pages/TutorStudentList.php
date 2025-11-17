<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Students - Tutor Portal</title>
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

        .controls {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }

        .search-box {
            flex: 1;
            min-width: 250px;
        }

        .search-box input {
            width: 100%;
            padding: 12px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1em;
            transition: border 0.3s;
        }

        .search-box input:focus {
            outline: none;
            border-color: #667eea;
        }

        .filter-select {
            padding: 12px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1em;
            cursor: pointer;
            background: white;
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .stats-bar {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-value {
            font-size: 2em;
            font-weight: bold;
            color: #667eea;
        }

        .stat-label {
            color: #666;
            font-size: 0.9em;
            margin-top: 5px;
        }

        .table-container {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85em;
            letter-spacing: 1px;
        }

        tbody tr {
            border-bottom: 1px solid #e0e0e0;
            transition: background 0.3s;
        }

        tbody tr:hover {
            background: #f8f9fa;
        }

        td {
            padding: 15px;
        }

        .student-name {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }

        .name-info h3 {
            color: #333;
            margin-bottom: 3px;
            font-size: 1em;
        }

        .name-info p {
            color: #888;
            font-size: 0.85em;
        }

        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: 600;
        }

        .badge-active {
            background: #d4edda;
            color: #155724;
        }

        .badge-pending {
            background: #fff3cd;
            color: #856404;
        }

        .badge-completed {
            background: #cce5ff;
            color: #004085;
        }

        .action-btns {
            display: flex;
            gap: 8px;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 0.85em;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-view {
            background: #667eea;
            color: white;
        }

        .btn-view:hover {
            background: #5568d3;
        }

        .btn-message {
            background: #28a745;
            color: white;
        }

        .btn-message:hover {
            background: #218838;
        }

        .rating-stars {
            color: #ffc107;
            font-size: 1.1em;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        @media (max-width: 768px) {
            .controls {
                flex-direction: column;
            }

            .search-box {
                width: 100%;
            }

            .table-container {
                padding: 15px;
            }

            table {
                font-size: 0.9em;
            }

            th, td {
                padding: 10px 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>👥 My Students</h1>
            <p>Manage and track your enrolled students</p>
        </div>

        <div class="controls">
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="🔍 Search students by name or email..." onkeyup="filterStudents()">
            </div>
            
            <select class="filter-select" id="statusFilter" onchange="filterStudents()">
                <option value="all">All Status</option>
                <option value="active">Active</option>
                <option value="pending">Pending</option>
                <option value="completed">Completed</option>
            </select>

            <select class="filter-select" id="courseFilter" onchange="filterStudents()">
                <option value="all">All Courses</option>
            </select>

            <button class="btn btn-primary" onclick="exportData()">📥 Export</button>
        </div>

        <div class="stats-bar">
            <div class="stat-item">
                <div class="stat-value" id="totalStudents">0</div>
                <div class="stat-label">Total Students</div>
            </div>
            <div class="stat-item">
                <div class="stat-value" id="activeStudents">0</div>
                <div class="stat-label">Active</div>
            </div>
            <div class="stat-item">
                <div class="stat-value" id="pendingStudents">0</div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-item">
                <div class="stat-value" id="avgSessions">0</div>
                <div class="stat-label">Avg Sessions</div>
            </div>
        </div>

        <div class="table-container">
            <table id="studentsTable">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Course</th>
                        <th>Status</th>
                        <th>Sessions</th>
                        <th>Next Session</th>
                        <th>Rating</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <tr>
                        <td colspan="7" class="no-data">Loading students...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        let studentsData = [];
        const tutorUserId = getCurrentTutorId();

        function getCurrentTutorId() {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get('tutorId') || sessionStorage.getItem('tutorUserId') || 1;
        }

        async function loadStudents() {
            try {
                // Replace with your actual API endpoint
                const response = await fetch(`/api/tutor-students.php?tutorId=${tutorUserId}`);
                studentsData = await response.json();
                
                displayStudents(studentsData);
                updateStats(studentsData);
                loadCourseFilter();
            } catch (error) {
                console.error('Error loading students:', error);
                // Load demo data
                studentsData = generateDemoData();
                displayStudents(studentsData);
                updateStats(studentsData);
                loadCourseFilter();
            }
        }

        function generateDemoData() {
            return [
                {
                    id: 1,
                    firstName: 'John',
                    lastName: 'Doe',
                    email: 'john.doe@email.com',
                    courseName: 'Mathematics 101',
                    status: 'active',
                    sessions: 12,
                    nextSession: '2024-11-20',
                    rating: 5
                },
                {
                    id: 2,
                    firstName: 'Jane',
                    lastName: 'Smith',
                    email: 'jane.smith@email.com',
                    courseName: 'Physics Advanced',
                    status: 'active',
                    sessions: 8,
                    nextSession: '2024-11-19',
                    rating: 4
                },
                {
                    id: 3,
                    firstName: 'Mike',
                    lastName: 'Johnson',
                    email: 'mike.j@email.com',
                    courseName: 'Chemistry Basics',
                    status: 'pending',
                    sessions: 3,
                    nextSession: '2024-11-21',
                    rating: 5
                },
                {
                    id: 4,
                    firstName: 'Sarah',
                    lastName: 'Williams',
                    email: 'sarah.w@email.com',
                    courseName: 'Mathematics 101',
                    status: 'active',
                    sessions: 15,
                    nextSession: '2024-11-18',
                    rating: 5
                },
                {
                    id: 5,
                    firstName: 'David',
                    lastName: 'Brown',
                    email: 'david.b@email.com',
                    courseName: 'Biology 201',
                    status: 'completed',
                    sessions: 20,
                    nextSession: '-',
                    rating: 4
                }
            ];
        }

        function displayStudents(students) {
            const tableBody = document.getElementById('tableBody');
            
            if (!students || students.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="7" class="no-data">No students found</td></tr>';
                return;
            }

            tableBody.innerHTML = students.map(student => {
                const initials = `${student.firstName[0]}${student.lastName[0]}`;
                const stars = '⭐'.repeat(student.rating || 0);
                const statusClass = `badge-${student.status}`;
                
                return `
                    <tr>
                        <td>
                            <div class="student-name">
                                <div class="avatar">${initials}</div>
                                <div class="name-info">
                                    <h3>${student.firstName} ${student.lastName}</h3>
                                    <p>${student.email}</p>
                                </div>
                            </div>
                        </td>
                        <td>${student.courseName}</td>
                        <td><span class="badge ${statusClass}">${student.status}</span></td>
                        <td>${student.sessions}</td>
                        <td>${student.nextSession}</td>
                        <td><span class="rating-stars">${stars}</span></td>
                        <td>
                            <div class="action-btns">
                                <button class="btn-sm btn-view" onclick="viewStudent(${student.id})">View</button>
                                <button class="btn-sm btn-message" onclick="messageStudent(${student.id})">Message</button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        function updateStats(students) {
            const total = students.length;
            const active = students.filter(s => s.status === 'active').length;
            const pending = students.filter(s => s.status === 'pending').length;
            const avgSessions = total > 0 ? Math.round(students.reduce((sum, s) => sum + s.sessions, 0) / total) : 0;

            document.getElementById('totalStudents').textContent = total;
            document.getElementById('activeStudents').textContent = active;
            document.getElementById('pendingStudents').textContent = pending;
            document.getElementById('avgSessions').textContent = avgSessions;
        }

        function loadCourseFilter() {
            const courses = [...new Set(studentsData.map(s => s.courseName))];
            const courseFilter = document.getElementById('courseFilter');
            
            courseFilter.innerHTML = '<option value="all">All Courses</option>';
            courses.forEach(course => {
                courseFilter.innerHTML += `<option value="${course}">${course}</option>`;
            });
        }

        function filterStudents() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const statusFilter = document.getElementById('statusFilter').value;
            const courseFilter = document.getElementById('courseFilter').value;

            const filtered = studentsData.filter(student => {
                const matchesSearch = 
                    student.firstName.toLowerCase().includes(searchTerm) ||
                    student.lastName.toLowerCase().includes(searchTerm) ||
                    student.email.toLowerCase().includes(searchTerm);
                
                const matchesStatus = statusFilter === 'all' || student.status === statusFilter;
                const matchesCourse = courseFilter === 'all' || student.courseName === courseFilter;

                return matchesSearch && matchesStatus && matchesCourse;
            });

            displayStudents(filtered);
            updateStats(filtered);
        }

        function viewStudent(studentId) {
            window.location.href = `student-details.html?id=${studentId}`;
        }

        function messageStudent(studentId) {
            alert(`Opening message dialog for student ID: ${studentId}`);
            // Implement messaging functionality
        }

        function exportData() {
            const csv = convertToCSV(studentsData);
            downloadCSV(csv, 'students_data.csv');
        }

        function convertToCSV(data) {
            const headers = ['Name', 'Email', 'Course', 'Status', 'Sessions', 'Next Session', 'Rating'];
            const rows = data.map(s => [
                `${s.firstName} ${s.lastName}`,
                s.email,
                s.courseName,
                s.status,
                s.sessions,
                s.nextSession,
                s.rating
            ]);

            return [headers, ...rows].map(row => row.join(',')).join('\n');
        }

        function downloadCSV(csv, filename) {
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            a.click();
            window.URL.revokeObjectURL(url);
        }

        // Load students on page load
        window.onload = loadStudents;
    </script>
</body>
</html>