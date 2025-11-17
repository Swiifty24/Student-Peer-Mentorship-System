<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Printables - Tutor Portal</title>
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

        .printables-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .printable-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            transition: all 0.3s;
        }

        .printable-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.25);
        }

        .printable-icon {
            font-size: 3em;
            margin-bottom: 15px;
        }

        .printable-title {
            font-size: 1.3em;
            color: #333;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .printable-description {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 20px;
            line-height: 1.5;
        }

        .printable-actions {
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

        .btn-preview {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            flex: 1;
        }

        .btn-preview:hover {
            transform: scale(1.05);
        }

        .btn-print {
            background: #28a745;
            color: white;
        }

        .btn-print:hover {
            background: #218838;
        }

        .print-area {
            display: none;
        }

        /* Print Styles */
        @media print {
            body {
                background: white;
                padding: 0;
            }

            .container {
                max-width: 100%;
            }

            .header,
            .printables-grid,
            .btn,
            .no-print {
                display: none !important;
            }

            .print-area {
                display: block !important;
            }

            .print-document {
                page-break-after: always;
            }

            .print-document:last-child {
                page-break-after: auto;
            }
        }

        /* Document Templates */
        .print-document {
            background: white;
            padding: 40px;
            margin: 20px 0;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .doc-header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #667eea;
            padding-bottom: 20px;
        }

        .doc-header h2 {
            color: #667eea;
            font-size: 2em;
            margin-bottom: 10px;
        }

        .doc-info {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }

        .info-label {
            font-weight: 600;
            color: #666;
        }

        .info-value {
            color: #333;
        }

        .doc-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        .doc-table th {
            background: #667eea;
            color: white;
            padding: 12px;
            text-align: left;
        }

        .doc-table td {
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
        }

        .doc-footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #e0e0e0;
            text-align: center;
            color: #666;
        }

        .signature-line {
            margin-top: 50px;
            border-top: 2px solid #333;
            width: 300px;
            padding-top: 10px;
            margin-left: auto;
            margin-right: auto;
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
            overflow-y: auto;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            padding: 40px;
            border-radius: 15px;
            max-width: 900px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            margin: 20px;
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
            .printables-grid {
                grid-template-columns: 1fr;
            }

            .doc-info {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header no-print">
            <h1>🖨️ Printables & Documents</h1>
            <p>Generate and print professional documents for your tutoring sessions</p>
        </div>

        <div class="printables-grid no-print">
            <div class="printable-card">
                <div class="printable-icon">📄</div>
                <div class="printable-title">Session Certificate</div>
                <div class="printable-description">
                    Generate completion certificates for students who have finished their tutoring sessions.
                </div>
                <div class="printable-actions">
                    <button class="btn btn-preview" onclick="previewDocument('certificate')">Preview</button>
                    <button class="btn btn-print" onclick="printDocument('certificate')">🖨️ Print</button>
                </div>
            </div>

            <div class="printable-card">
                <div class="printable-icon">📋</div>
                <div class="printable-title">Attendance Sheet</div>
                <div class="printable-description">
                    Print attendance tracking sheets for your tutoring sessions and courses.
                </div>
                <div class="printable-actions">
                    <button class="btn btn-preview" onclick="previewDocument('attendance')">Preview</button>
                    <button class="btn btn-print" onclick="printDocument('attendance')">🖨️ Print</button>
                </div>
            </div>

            <div class="printable-card">
                <div class="printable-icon">📊</div>
                <div class="printable-title">Progress Report</div>
                <div class="printable-description">
                    Create detailed progress reports for individual students with performance metrics.
                </div>
                <div class="printable-actions">
                    <button class="btn btn-preview" onclick="previewDocument('progress')">Preview</button>
                    <button class="btn btn-print" onclick="printDocument('progress')">🖨️ Print</button>
                </div>
            </div>

            <div class="printable-card">
                <div class="printable-icon">💳</div>
                <div class="printable-title">Invoice</div>
                <div class="printable-description">
                    Generate professional invoices for your tutoring services and session payments.
                </div>
                <div class="printable-actions">
                    <button class="btn btn-preview" onclick="previewDocument('invoice')">Preview</button>
                    <button class="btn btn-print" onclick="printDocument('invoice')">🖨️ Print</button>
                </div>
            </div>

            <div class="printable-card">
                <div class="printable-icon">📅</div>
                <div class="printable-title">Session Schedule</div>
                <div class="printable-description">
                    Print weekly or monthly schedules of all your tutoring sessions and appointments.
                </div>
                <div class="printable-actions">
                    <button class="btn btn-preview" onclick="previewDocument('schedule')">Preview</button>
                    <button class="btn btn-print" onclick="printDocument('schedule')">🖨️ Print</button>
                </div>
            </div>

            <div class="printable-card">
                <div class="printable-icon">📝</div>
                <div class="printable-title">Session Notes</div>
                <div class="printable-description">
                    Print formatted session notes and learning objectives for student records.
                </div>
                <div class="printable-actions">
                    <button class="btn btn-preview" onclick="previewDocument('notes')">Preview</button>
                    <button class="btn btn-print" onclick="printDocument('notes')">🖨️ Print</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Print Area -->
    <div class="print-area" id="printArea"></div>

    <!-- Preview Modal -->
    <div class="modal" id="previewModal">
        <div class="modal-content">
            <div class="modal-header no-print">
                <h3>Document Preview</h3>
                <span class="close-btn" onclick="closeModal()">&times;</span>
            </div>
            <div id="previewContent"></div>
            <div class="no-print" style="margin-top: 20px; text-align: center;">
                <button class="btn btn-print" onclick="printFromModal()">🖨️ Print Document</button>
            </div>
        </div>
    </div>

    <script>
        const tutorUserId = getCurrentTutorId();
        let currentDocType = '';

        function getCurrentTutorId() {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get('tutorId') || sessionStorage.getItem('tutorUserId') || 1;
        }

        const documentTemplates = {
            certificate: `
                <div class="print-document">
                    <div class="doc-header">
                        <h2>🎓 Certificate of Completion</h2>
                        <p>Student Peer Mentorship System</p>
                    </div>
                    <div style="text-align: center; margin: 50px 0;">
                        <h3 style="font-size: 1.2em; color: #666; margin-bottom: 30px;">This certifies that</h3>
                        <h1 style="font-size: 2.5em; color: #667eea; margin-bottom: 30px;">John Doe</h1>
                        <h3 style="font-size: 1.2em; color: #666; margin-bottom: 20px;">has successfully completed</h3>
                        <h2 style="font-size: 2em; color: #333; margin-bottom: 40px;">Mathematics 101 Tutoring Program</h2>
                        <p style="color: #666; margin-bottom: 10px;">Total Sessions: 20</p>
                        <p style="color: #666; margin-bottom: 40px;">Completion Date: November 17, 2025</p>
                    </div>
                    <div class="signature-line">
                        <p style="text-align: center; font-weight: 600;">Tutor Signature</p>
                    </div>
                    <div class="doc-footer">
                        <p>Student Peer Mentorship System | Generated on ${new Date().toLocaleDateString()}</p>
                    </div>
                </div>
            `,
            attendance: `
                <div class="print-document">
                    <div class="doc-header">
                        <h2>📋 Attendance Sheet</h2>
                        <p>Course: Mathematics 101</p>
                    </div>
                    <div class="doc-info">
                        <div class="info-item">
                            <span class="info-label">Tutor:</span>
                            <span class="info-value">Tutor Name</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Period:</span>
                            <span class="info-value">November 2025</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Total Students:</span>
                            <span class="info-value">12</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Generated:</span>
                            <span class="info-value">${new Date().toLocaleDateString()}</span>
                        </div>
                    </div>
                    <table class="doc-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Student Name</th>
                                <th>Session Time</th>
                                <th>Status</th>
                                <th>Signature</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td>Nov 15, 2025</td><td>John Doe</td><td>2:00 PM</td><td>✓ Present</td><td></td></tr>
                            <tr><td>Nov 15, 2025</td><td>Jane Smith</td><td>3:00 PM</td><td>✓ Present</td><td></td></tr>
                            <tr><td>Nov 16, 2025</td><td>Mike Johnson</td><td>1:00 PM</td><td>✗ Absent</td><td></td></tr>
                            <tr><td>Nov 16, 2025</td><td>Sarah Williams</td><td>4:00 PM</td><td>✓ Present</td><td></td></tr>
                            <tr><td>Nov 17, 2025</td><td>David Brown</td><td>2:30 PM</td><td>✓ Present</td><td></td></tr>
                        </tbody>
                    </table>
                    <div class="doc-footer">
                        <p>Student Peer Mentorship System</p>
                    </div>
                </div>
            `,
            progress: `
                <div class="print-document">
                    <div class="doc-header">
                        <h2>📊 Student Progress Report</h2>
                        <p>Detailed Performance Analysis</p>
                    </div>
                    <div class="doc-info">
                        <div class="info-item">
                            <span class="info-label">Student:</span>
                            <span class="info-value">John Doe</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Course:</span>
                            <span class="info-value">Mathematics 101</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Tutor:</span>
                            <span class="info-value">Tutor Name</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Period:</span>
                            <span class="info-value">Sep - Nov 2025</span>
                        </div>
                    </div>
                    <h3 style="color: #667eea; margin: 20px 0;">Performance Metrics</h3>
                    <table class="doc-table">
                        <thead>
                            <tr>
                                <th>Metric</th>
                                <th>Score</th>
                                <th>Progress</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td>Attendance Rate</td><td>95%</td><td>↑ Excellent</td></tr>
                            <tr><td>Assignment Completion</td><td>88%</td><td>↑ Good</td></tr>
                            <tr><td>Test Performance</td><td>85%</td><td>↑ Improving</td></tr>
                            <tr><td>Engagement Level</td><td>90%</td><td>→ Consistent</td></tr>
                        </tbody>
                    </table>
                    <h3 style="color: #667eea; margin: 20px 0;">Tutor Comments</h3>
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                        <p style="line-height: 1.8;">John has shown exceptional progress throughout the semester. His understanding of algebraic concepts has improved significantly, and he consistently completes assignments on time. Recommended areas for continued focus: advanced problem-solving and word problems.</p>
                    </div>
                    <div class="doc-footer">
                        <p>Student Peer Mentorship System | Generated on ${new Date().toLocaleDateString()}</p>
                    </div>
                </div>
            `,
            invoice: `
                <div class="print-document">
                    <div class="doc-header">
                        <h2>💳 Invoice</h2>
                        <p>Professional Tutoring Services</p>
                    </div>
                    <div class="doc-info">
                        <div class="info-item">
                            <span class="info-label">Invoice #:</span>
                            <span class="info-value">INV-2025-001</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Date:</span>
                            <span class="info-value">${new Date().toLocaleDateString()}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Student:</span>
                            <span class="info-value">John Doe</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Due Date:</span>
                            <span class="info-value">${new Date(Date.now() + 7*24*60*60*1000).toLocaleDateString()}</span>
                        </div>
                    </div>
                    <table class="doc-table">
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th>Hours</th>
                                <th>Rate</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td>Mathematics 101 Tutoring</td><td>10</td><td>$30.00</td><td>$300.00</td></tr>
                            <tr><td>Materials & Resources</td><td>-</td><td>-</td><td>$25.00</td></tr>
                            <tr><td colspan="3" style="text-align: right; font-weight: 600;">Subtotal:</td><td>$325.00</td></tr>
                            <tr><td colspan="3" style="text-align: right; font-weight: 600;">Tax (0%):</td><td>$0.00</td></tr>
                            <tr style="background: #f8f9fa;"><td colspan="3" style="text-align: right; font-weight: bold; font-size: 1.2em;">Total:</td><td style="font-weight: bold; font-size: 1.2em;">$325.00</td></tr>
                        </tbody>
                    </table>
                    <div style="margin-top: 30px; padding: 15px; background: #fff3cd; border-radius: 8px;">
                        <p style="font-weight: 600; color: #856404;">Payment Instructions:</p>
                        <p style="color: #856404; margin-top: 10px;">Please make payment via bank transfer or contact the administration office for other payment methods.</p>
                    </div>
                    <div class="doc-footer">
                        <p>Thank you for your business!</p>
                    </div>
                </div>
            `,
            schedule: `
                <div class="print-document">
                    <div class="doc-header">
                        <h2>📅 Session Schedule</h2>
                        <p>Weekly Tutoring Calendar</p>
                    </div>
                    <div class="doc-info">
                        <div class="info-item">
                            <span class="info-label">Tutor:</span>
                            <span class="info-value">Tutor Name</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Week of:</span>
                            <span class="info-value">November 17-23, 2025</span>
                        </div>
                    </div>
                    <table class="doc-table">
                        <thead>
                            <tr>
                                <th>Day</th>
                                <th>Time</th>
                                <th>Student</th>
                                <th>Course</th>
                                <th>Location</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td>Monday</td><td>2:00 PM - 3:30 PM</td><td>John Doe</td><td>Math 101</td><td>Room 201</td></tr>
                            <tr><td>Monday</td><td>4:00 PM - 5:00 PM</td><td>Jane Smith</td><td>Physics</td><td>Room 205</td></tr>
                            <tr><td>Tuesday</td><td>1:00 PM - 2:30 PM</td><td>Mike Johnson</td><td>Chemistry</td><td>Lab 3</td></tr>
                            <tr><td>Wednesday</td><td>3:00 PM - 4:00 PM</td><td>Sarah Williams</td><td>Math 101</td><td>Room 201</td></tr>
                            <tr><td>Thursday</td><td>2:30 PM - 4:00 PM</td><td>David Brown</td><td>Biology</td><td>Lab 2</td></tr>
                            <tr><td>Friday</td><td>1:00 PM - 2:00 PM</td><td>John Doe</td><td>Math 101</td><td>Room 201</td></tr>
                        </tbody>
                    </table>
                    <div class="doc-footer">
                        <p>Student Peer Mentorship System</p>
                    </div>
                </div>
            `,
            notes: `
                <div class="print-document">
                    <div class="doc-header">
                        <h2>📝 Session Notes</h2>
                        <p>Detailed Session Documentation</p>
                    </div>
                    <div class="doc-info">
                        <div class="info-item">
                            <span class="info-label">Date:</span>
                            <span class="info-value">${new Date().toLocaleDateString()}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Student:</span>
                            <span class="info-value">John Doe</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Course:</span>
                            <span class="info-value">Mathematics 101</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Duration:</span>
                            <span class="info-value">1.5 hours</span>
                        </div>
                    </div>
                    <h3 style="color: #667eea; margin: 20px 0;">Topics Covered</h3>
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                        <ul style="line-height: 2; margin-left: 20px;">
                            <li>Quadratic equations and their solutions</li>
                            <li>Graphing parabolas</li>
                            <li>Real-world applications of quadratic functions</li>
                        </ul>
                    </div>
                    <h3 style="color: #667eea; margin: 20px 0;">Learning Objectives Achieved</h3>
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                        <p style="line-height: 1.8;">✓ Student can solve quadratic equations using the quadratic formula<br>
                        ✓ Student understands how to complete the square<br>
                        ✓ Student can identify the vertex of a parabola</p>
                    </div>
                    <h3 style="color: #667eea; margin: 20px 0;">Homework Assigned</h3>
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                        <p style="line-height: 1.8;">Chapter 5, Problems 1-15 (odd numbers)<br>
                        Practice worksheet on graphing parabolas<br>
                        Due: Next session</p>
                    </div>
                    <h3 style="color: #667eea; margin: 20px 0;">Notes for Next Session</h3>
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                        <p style="line-height: 1.8;">Continue with graphing techniques. Student needs additional practice with word problems involving quadratic functions.</p>
                    </div>
                    <div class="doc-footer">
                        <p>Student Peer Mentorship System | Generated on ${new Date().toLocaleDateString()}</p>
                    </div>
                </div>
            `
        };

        function previewDocument(docType) {
            currentDocType = docType;
            const content = documentTemplates[docType];
            document.getElementById('previewContent').innerHTML = content;
            document.getElementById('previewModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('previewModal').classList.remove('active');
        }

        function printDocument(docType) {
            const content = documentTemplates[docType];
            document.getElementById('printArea').innerHTML = content;
            window.print();
        }

        function printFromModal() {
            closeModal();
            setTimeout(() => {
                printDocument(currentDocType);
            }, 300);
        }
    </script>
</body>
</html>