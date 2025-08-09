<?php
// Database setup script for BatEstate
$host = 'localhost';
$username = 'root';
$password = '';

// Create connection without database
$conn = mysqli_connect($host, $username, $password);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

echo "✅ Connected to MySQL server\n";

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS batestate";
if (mysqli_query($conn, $sql)) {
    echo "✅ Database 'batestate' created successfully\n";
} else {
    echo "❌ Error creating database: " . mysqli_error($conn) . "\n";
}

// Select the database
mysqli_select_db($conn, 'batestate');

// Create users table
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    user_type ENUM('user', 'direct_agent', 'associate_agent', 'admin') DEFAULT 'user',
    status ENUM('active', 'inactive', 'pending') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $sql)) {
    echo "✅ Table 'users' created successfully\n";
} else {
    echo "❌ Error creating users table: " . mysqli_error($conn) . "\n";
}

// Create companies table
$sql = "CREATE TABLE IF NOT EXISTS companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    address TEXT,
    phone VARCHAR(20),
    email VARCHAR(255),
    website VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $sql)) {
    echo "✅ Table 'companies' created successfully\n";
} else {
    echo "❌ Error creating companies table: " . mysqli_error($conn) . "\n";
}

// Create agents table
$sql = "CREATE TABLE IF NOT EXISTS agents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    company_id INT,
    broker_id VARCHAR(100),
    license_number VARCHAR(100),
    experience_years INT DEFAULT 0,
    specialization VARCHAR(255),
    bio TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE SET NULL
)";

if (mysqli_query($conn, $sql)) {
    echo "✅ Table 'agents' created successfully\n";
} else {
    echo "❌ Error creating agents table: " . mysqli_error($conn) . "\n";
}

// Create applications table
$sql = "CREATE TABLE IF NOT EXISTS applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    agent_type ENUM('direct_agent', 'associate_agent') NOT NULL,
    company_id INT,
    broker_id VARCHAR(100),
    license_number VARCHAR(100),
    experience_years INT DEFAULT 0,
    specialization VARCHAR(255),
    bio TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    admin_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE SET NULL
)";

if (mysqli_query($conn, $sql)) {
    echo "✅ Table 'applications' created successfully\n";
} else {
    echo "❌ Error creating applications table: " . mysqli_error($conn) . "\n";
}

// Create agent_qualifications table
$sql = "CREATE TABLE IF NOT EXISTS agent_qualifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agent_id INT NOT NULL,
    qualification_type VARCHAR(100) NOT NULL,
    qualification_name VARCHAR(255) NOT NULL,
    issuing_organization VARCHAR(255),
    issue_date DATE,
    expiry_date DATE,
    document_path VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (agent_id) REFERENCES agents(id) ON DELETE CASCADE
)";

if (mysqli_query($conn, $sql)) {
    echo "✅ Table 'agent_qualifications' created successfully\n";
} else {
    echo "❌ Error creating agent_qualifications table: " . mysqli_error($conn) . "\n";
}

// Create properties table
$sql = "CREATE TABLE IF NOT EXISTS properties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    property_type ENUM('Lot', 'Property') NOT NULL,
    location VARCHAR(255) NOT NULL,
    price DECIMAL(15,2) NOT NULL,
    bedrooms INT DEFAULT 0,
    bathrooms INT DEFAULT 0,
    sqm DECIMAL(10,2),
    lot_size DECIMAL(10,2),
    agent_id INT,
    status ENUM('available', 'sold', 'pending') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (agent_id) REFERENCES agents(id) ON DELETE SET NULL
)";

if (mysqli_query($conn, $sql)) {
    echo "✅ Table 'properties' created successfully\n";
} else {
    echo "❌ Error creating properties table: " . mysqli_error($conn) . "\n";
}

// Create property_images table
$sql = "CREATE TABLE IF NOT EXISTS property_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    property_id INT NOT NULL,
    image_path VARCHAR(500) NOT NULL,
    is_primary BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
)";

if (mysqli_query($conn, $sql)) {
    echo "✅ Table 'property_images' created successfully\n";
} else {
    echo "❌ Error creating property_images table: " . mysqli_error($conn) . "\n";
}

// Create property_documents table
$sql = "CREATE TABLE IF NOT EXISTS property_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    property_id INT NOT NULL,
    document_path VARCHAR(500) NOT NULL,
    document_type VARCHAR(100),
    document_name VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
)";

if (mysqli_query($conn, $sql)) {
    echo "✅ Table 'property_documents' created successfully\n";
} else {
    echo "❌ Error creating property_documents table: " . mysqli_error($conn) . "\n";
}

// Create messages table
$sql = "CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    subject VARCHAR(255),
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
)";

if (mysqli_query($conn, $sql)) {
    echo "✅ Table 'messages' created successfully\n";
} else {
    echo "❌ Error creating messages table: " . mysqli_error($conn) . "\n";
}

// Drop and recreate companies table to fix structure
// First drop dependent tables
$sql = "DROP TABLE IF EXISTS agent_qualifications";
mysqli_query($conn, $sql);

$sql = "DROP TABLE IF EXISTS agents";
mysqli_query($conn, $sql);

$sql = "DROP TABLE IF EXISTS applications";
mysqli_query($conn, $sql);

$sql = "DROP TABLE IF EXISTS companies";
mysqli_query($conn, $sql);

$sql = "CREATE TABLE companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    address TEXT,
    phone VARCHAR(20),
    email VARCHAR(255),
    website VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $sql)) {
    echo "✅ Table 'companies' recreated successfully\n";
} else {
    echo "❌ Error recreating companies table: " . mysqli_error($conn) . "\n";
}

// Recreate agents table
$sql = "CREATE TABLE agents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    company_id INT,
    broker_id VARCHAR(100),
    license_number VARCHAR(100),
    experience_years INT DEFAULT 0,
    specialization VARCHAR(255),
    bio TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE SET NULL
)";

if (mysqli_query($conn, $sql)) {
    echo "✅ Table 'agents' recreated successfully\n";
} else {
    echo "❌ Error recreating agents table: " . mysqli_error($conn) . "\n";
}

// Recreate applications table
$sql = "CREATE TABLE applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    agent_type ENUM('direct_agent', 'associate_agent') NOT NULL,
    company_id INT,
    broker_id VARCHAR(100),
    license_number VARCHAR(100),
    experience_years INT DEFAULT 0,
    specialization VARCHAR(255),
    bio TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    admin_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE SET NULL
)";

if (mysqli_query($conn, $sql)) {
    echo "✅ Table 'applications' recreated successfully\n";
} else {
    echo "❌ Error recreating applications table: " . mysqli_error($conn) . "\n";
}

// Recreate agent_qualifications table
$sql = "CREATE TABLE agent_qualifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agent_id INT NOT NULL,
    qualification_type VARCHAR(100) NOT NULL,
    qualification_name VARCHAR(255) NOT NULL,
    issuing_organization VARCHAR(255),
    issue_date DATE,
    expiry_date DATE,
    document_path VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (agent_id) REFERENCES agents(id) ON DELETE CASCADE
)";

if (mysqli_query($conn, $sql)) {
    echo "✅ Table 'agent_qualifications' recreated successfully\n";
} else {
    echo "❌ Error recreating agent_qualifications table: " . mysqli_error($conn) . "\n";
}

// Insert sample companies
$companies = [
    ['Real Estate Pro Batangas', 'Leading real estate company in Batangas', 'Batangas City', '09123456789', 'info@realestatepro.com', 'www.realestatepro.com'],
    ['Batangas Properties Inc.', 'Your trusted partner in property investment', 'Lipa City', '09234567890', 'contact@batangasproperties.com', 'www.batangasproperties.com'],
    ['Prime Real Estate Batangas', 'Premium properties for discerning clients', 'Tanauan City', '09345678901', 'hello@primerealestate.com', 'www.primerealestate.com']
];

foreach ($companies as $company) {
    $sql = "INSERT INTO companies (name, description, address, phone, email, website) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssssss", $company[0], $company[1], $company[2], $company[3], $company[4], $company[5]);
    if (mysqli_stmt_execute($stmt)) {
        echo "✅ Company inserted: " . $company[0] . "\n";
    } else {
        echo "❌ Error inserting company: " . mysqli_error($conn) . "\n";
    }
}

echo "✅ Sample companies inserted\n";

// Insert admin user
$admin_email = 'admin@batestate.com';
$admin_password = password_hash('admin123', PASSWORD_DEFAULT);
$admin_first_name = 'Admin';
$admin_last_name = 'User';

$sql = "INSERT IGNORE INTO users (email, password_hash, first_name, last_name, user_type) VALUES (?, ?, ?, ?, 'admin')";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ssss", $admin_email, $admin_password, $admin_first_name, $admin_last_name);
mysqli_stmt_execute($stmt);

echo "✅ Admin user created (email: admin@batestate.com, password: admin123)\n";

// Insert sample properties
$properties = [
    ['Modern Family Home', 'Beautiful 3-bedroom house with garden', 'Property', 'Batangas City', 3500000, 3, 2, 180, 200, 1],
    ['Premium Lot in Lipa', 'Prime location for your dream home', 'Lot', 'Lipa City', 2500000, 0, 0, 0, 300, 1],
    ['Beachfront Property', 'Stunning beachfront house with ocean view', 'Property', 'Nasugbu', 8500000, 4, 3, 250, 500, 1],
    ['Affordable Starter Home', 'Perfect for young families', 'Property', 'Tanauan City', 1800000, 2, 1, 120, 150, 1],
    ['Investment Lot', 'Great investment opportunity', 'Lot', 'Calaca', 1200000, 0, 0, 0, 200, 1]
];

foreach ($properties as $property) {
    $sql = "INSERT IGNORE INTO properties (title, description, property_type, location, price, bedrooms, bathrooms, sqm, lot_size, agent_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssssdidddi", $property[0], $property[1], $property[2], $property[3], $property[4], $property[5], $property[6], $property[7], $property[8], $property[9]);
    mysqli_stmt_execute($stmt);
}

echo "✅ Sample properties inserted\n";

echo "\n🎉 Database setup completed successfully!\n";
echo "📋 Admin credentials:\n";
echo "   Email: admin@batestate.com\n";
echo "   Password: admin123\n";
echo "\n🚀 Your BatEstate website is ready to use!\n";

mysqli_close($conn);
?> 