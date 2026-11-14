# AAMS- Automated Attendance Management System

A comprehensive attendance management system that uses AWS Rekognition for facial recognition to automate student attendance tracking.

## Features

- **Facial Recognition**: Uses AWS Rekognition to identify students and mark attendance
- **Role-based Access Control**: Three user roles (Admin, Teacher, Student) with different permissions
- **Academic Structure**: Support for branches, semesters, and classes
- **Student Management**: Registration with facial data enrollment
- **Attendance Management**: Mark attendance via facial recognition or manual entry
- **Reporting**: Detailed attendance reports and statistics
- **Responsive Design**: Works on desktop and mobile devices

## Tech Stack

- **Backend**: PHP
- **Database**: SQLite
- **Frontend**: HTML, CSS, JavaScript
- **CSS Framework**: Bootstrap 5
- **Icons**: Bootstrap Icons
- **Cloud Services**: AWS Rekognition

## Requirements

- PHP 7.4 or higher
- SQLite3 extension for PHP
- GD or Imagick extension for PHP
- AWS account with Rekognition access

## Installation

1. Clone the repository:
   ```
   git clone https://github.com/yourusername/face-recognition-attendance.git
   cd face-recognition-attendance
   ```

2. Install PHP dependencies via Composer:
   ```
   composer install
   ```

3. Configure your AWS credentials:
   - Open `config/config.php`
   - Update the AWS_KEY, AWS_SECRET, AWS_REGION, and AWS_COLLECTION_ID values

4. Initialize the database:
   ```
   php db/init_db.php
   ```

5. Set appropriate permissions:
   ```
   chmod -R 755 uploads/
   chmod 777 db/attendance.db
   ```

6. Configure your web server (Apache/Nginx) to point to the project directory

## Default Login

After installation, you can log in with the default admin account:
- Username: admin
- Password: admin123

It is recommended to change this password immediately after the first login.

## Project Structure

- `/admin` - Admin functionality
- `/attendance` - Attendance marking and reporting
- `/assets` - CSS, JS, and image files
- `/config` - Configuration files
- `/db` - Database schema and initialization
- `/includes` - Core classes and helpers
- `/student` - Student functionality
- `/teacher` - Teacher functionality
- `/uploads` - Uploaded images (student photos and attendance captures)

## User Roles

1. **Admin**
   - Manage users, branches, semesters, and classes
   - Register students with facial data
   - Access all attendance reports
   - Access system settings

2. **Teacher**
   - Mark attendance using facial recognition
   - Register students with facial data
   - View and manage attendance for their classes
   - Generate attendance reports

3. **Student**
   - View their own attendance records
   - Check attendance statistics

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Acknowledgments

- [AWS SDK for PHP](https://github.com/aws/aws-sdk-php)
- [Bootstrap](https://getbootstrap.com/)
- [Bootstrap Icons](https://icons.getbootstrap.com/) 
