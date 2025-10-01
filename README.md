# BatEstate Explorer

A comprehensive real estate platform built with PHP, featuring user management, property listings, and agent management systems.

## 🏗️ Project Structure

```
BatEstateExplorer/
├── index.php                 # Main hero page (public landing)
├── public/                   # Public access files
│   ├── dashboard.php         # Main dashboard redirector
│   ├── admin/                # Admin section
│   │   └── admin_dashboard.php
│   ├── agent/                # Agent section
│   │   └── agent_dashboard.php
│   └── user/                 # User section
│       └── user_dashboard.php
├── auth/                     # Authentication files
│   ├── login.php
│   ├── signup_user.php
│   └── logout.php
├── app/                      # Application core
│   ├── bootstrap.php         # Application bootstrap
│   ├── Core/
│   │   └── Autoloader.php    # PSR-4 autoloader
│   ├── Controllers/          # Controller classes
│   │   ├── AdminController.php
│   │   ├── AgentController.php
│   │   └── UserController.php
│   └── Views/                # View templates
│       ├── admin/            # Admin views
│       ├── agent/            # Agent views
│       ├── user/             # User views
│       └── layout/           # Layout components
├── api/                      # API endpoints
│   ├── get_properties.php
│   ├── get_property_details.php
│   ├── get_agent_details.php
│   └── ...
├── database/                 # Database operations
│   ├── setup_database.php
│   ├── add_property.php
│   ├── update_property.php
│   ├── delete_property.php
│   └── ...
├── assets/                   # Static assets
│   ├── css/
│   │   ├── hero.css         # Hero page styles
│   │   ├── homepage.css     # General styles
│   │   └── admin_dashboard.css
│   ├── js/
│   │   ├── hero.js          # Hero page scripts
│   │   └── homepage.js      # General scripts
│   └── images/
│       └── bg4.jpg
├── storage/                  # File storage
│   └── uploads/
│       ├── property_images/
│       ├── documents/
│       └── images/
└── config/                   # Configuration files
    └── database.php
```

## 🚀 Getting Started

### Prerequisites
- PHP 7.4 or higher
- MySQL/MariaDB
- Web server (Apache/Nginx)
- XAMPP/WAMP/MAMP (for local development)

### Installation
1. Clone the repository to your web server directory
2. Import the database schema using `database/setup_database.php`
3. Configure database connection in `config/database.php`
4. Ensure the `storage/uploads/` directory is writable
5. Access the application through your web browser

### Entry Points
- **Root (`/`)**: Hero page with call-to-action
- **Admin (`/public/admin/`)**: Admin dashboard and management
- **Agent (`/public/agent/`)**: Agent dashboard and tools
- **User (`/public/user/`)**: User dashboard and property search

## 🎨 Features

### Public Features
- Modern, responsive hero page
- Property search and browsing
- User registration and login
- Agent registration

### Admin Features
- User management (direct/associate agents)
- Property listing management
- Application review system
- Performance analytics
- Report generation

### Agent Features
- Property management
- Client management
- Application handling
- Performance tracking

### User Features
- Property search and filtering
- Property details and images
- Application submission
- Profile management

## 🔧 Technical Details

### Architecture
- **MVC-like Structure**: Controllers handle logic, Views handle presentation
- **Front Controller Pattern**: Centralized routing through main entry points
- **PSR-4 Autoloading**: Automatic class loading with namespace support
- **Separation of Concerns**: CSS, JS, and PHP logic separated into appropriate directories

### Security Features
- Session-based authentication
- Role-based access control
- Input validation and sanitization
- Secure file upload handling

### File Organization
- **Inline Code Elimination**: All CSS and JavaScript extracted to separate files
- **Asset Centralization**: Static files organized in `assets/` directory
- **Upload Management**: File uploads centralized in `storage/uploads/`
- **API Separation**: Data endpoints isolated in `api/` directory

## 📱 Responsive Design

The application features a modern, responsive design that works seamlessly across:
- Desktop computers
- Tablets
- Mobile devices

## 🚀 Performance Optimizations

- Optimized asset loading
- Efficient database queries
- Image optimization
- Caching strategies

## 🔒 Security Considerations

- SQL injection prevention
- XSS protection
- CSRF protection
- File upload security
- Session security

## 📝 Development Guidelines

### Code Style
- Follow PSR-12 coding standards
- Use meaningful variable and function names
- Add comments for complex logic
- Maintain consistent indentation

### File Naming
- Use descriptive names for files and directories
- Follow kebab-case for file names
- Use PascalCase for class names
- Use camelCase for method names

### Database
- Use prepared statements for all queries
- Implement proper error handling
- Follow naming conventions for tables and columns
- Implement proper indexing

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 🆘 Support

For support and questions:
- Email: info@batestate.com
- Phone: +1 (555) 123-4567

## 🔄 Version History

- **v2.0.0**: Complete project restructuring and modernization
- **v1.0.0**: Initial release with basic functionality
