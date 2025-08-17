<?php

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent Registration - BatEstate Explorer</title>
    <link rel="stylesheet" href="../assets/css/hero.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .registration-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #007bff;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .submit-btn {
            background: #007bff;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 5px;
            font-size: 18px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .submit-btn:hover {
            background: #0056b3;
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #007bff;
            text-decoration: none;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="registration-container">
        <a href="../index.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Home
        </a>
        
        <h1><i class="fas fa-user-tie"></i> Agent Registration</h1>
        <p>Join our network of professional real estate agents and start your journey with BatEstate Explorer.</p>
        
        <form action="../public/api/agent_registration_complete.php" method="POST" enctype="multipart/form-data">
            <h3>Personal Information</h3>
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">First Name *</label>
                    <input type="text" id="first_name" name="first_name" required>
                </div>
                <div class="form-group">
                    <label for="last_name">Last Name *</label>
                    <input type="text" id="last_name" name="last_name" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="phone">Phone Number *</label>
                    <input type="tel" id="phone" name="phone" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="address">Address *</label>
                <textarea id="address" name="address" rows="3" required></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="user_type">Agent Type *</label>
                    <select id="user_type" name="user_type" required>
                        <option value="">Select Agent Type</option>
                        <option value="direct_agent">Direct Agent</option>
                        <option value="associate_agent">Associate Agent</option>
                    </select>
                </div>
            </div>
            
            <h3>Professional Information</h3>
            <div class="form-row">
                <div class="form-group">
                    <label for="broker_id">Broker ID</label>
                    <input type="text" id="broker_id" name="broker_id">
                </div>
                <div class="form-group">
                    <label for="prc_number">PRC Number</label>
                    <input type="text" id="prc_number" name="prc_number">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="experience_years">Years of Experience</label>
                    <select id="experience_years" name="experience_years">
                        <option value="">Select Experience</option>
                        <option value="0-1">0-1 years</option>
                        <option value="2-5">2-5 years</option>
                        <option value="6-10">6-10 years</option>
                        <option value="10+">10+ years</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="specializations">Specializations</label>
                    <input type="text" id="specializations" name="specializations" placeholder="e.g., Residential, Commercial, Luxury">
                </div>
            </div>
            
            <div class="form-group">
                <label for="experience_details">Experience Details</label>
                <textarea id="experience_details" name="experience_details" rows="4" placeholder="Describe your real estate experience and achievements"></textarea>
            </div>
            
            <h3>Educational Background</h3>
            <div class="form-row">
                <div class="form-group">
                    <label for="education">Education Level</label>
                    <select id="education" name="education">
                        <option value="">Select Education</option>
                        <option value="High School">High School</option>
                        <option value="Associate">Associate Degree</option>
                        <option value="Bachelor">Bachelor's Degree</option>
                        <option value="Master">Master's Degree</option>
                        <option value="PhD">PhD</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="school">School/University</label>
                    <input type="text" id="school" name="school">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="course">Course/Major</label>
                    <input type="text" id="course" name="course">
                </div>
                <div class="form-group">
                    <label for="graduation_year">Graduation Year</label>
                    <input type="number" id="graduation_year" name="graduation_year" min="1950" max="2030">
                </div>
            </div>
            
            <h3>Certifications & Training</h3>
            <div class="form-group">
                <label for="certifications">Professional Certifications</label>
                <textarea id="certifications" name="certifications" rows="3" placeholder="List any professional certifications you hold"></textarea>
            </div>
            
            <div class="form-group">
                <label for="training">Additional Training</label>
                <textarea id="training" name="training" rows="3" placeholder="List any additional training or workshops attended"></textarea>
            </div>
            
            <div class="form-group">
                <button type="submit" class="submit-btn">
                    <i class="fas fa-paper-plane"></i> Submit Application
                </button>
            </div>
        </form>
    </div>
</body>
</html>
