# 🧾 Advanced Filament Invoicing and Payment Management System

A comprehensive **Laravel-based invoicing and payment management system** built with [Filament Admin Panel](https://filamentphp.com/). This powerful application enables businesses to create, manage, and track invoices while providing customers with secure access to view and download their invoices and payment receipts.

---

## 🌟 Key Features

### 📋 Invoice Management
- **Multi-Format Invoice Creation** - Create invoices for individual customers or entire customer groups
- **Flexible Invoice Items** - Add unlimited items with descriptions, quantities, rates, and totals
- **Extra Charges Support** - Include additional charges like shipping, taxes, or fees
- **Invoice Status Tracking** - Track payment status (Paid, Pending, Overdue)
- **PDF Invoice Generation** - Automatically generate professional PDF invoices
- **Recurring Invoice Support** - Set up recurring billing cycles

### 👥 Customer Management
- **Individual Customer Profiles** - Comprehensive customer information with contact details
- **Customer Groups** - Organize customers into groups for bulk invoicing
- **Automatic Account Creation** - Customer login accounts created automatically
- **Customer Portal Access** - Secure customer login to view invoices and payments
- **Customer Group Billing** - Generate invoices for entire customer groups

### 🏢 Multi-Biller Support
- **Multiple Business Entities** - Manage multiple billers/companies
- **Custom Biller Profiles** - Complete business information with logos
- **Flexible Billing** - Generate invoices under any biller profile
- **Business Branding** - Custom logos and business details on invoices

### 💰 Payment Management
- **Payment Tracking** - Record and track all invoice payments
- **Payment Status Updates** - Automatic calculation of amounts due
- **Payment History** - Complete payment audit trail
- **Payment Reports** - Generate comprehensive payment reports
- **Export Functionality** - Export payment data to Excel

### 📊 Advanced Reporting & Analytics
- **Payment Reports** - Detailed payment analysis and reporting
- **Invoice Analytics** - Track invoice performance and trends
- **Excel Export** - Export invoices and payment data to Excel
- **PDF Reports** - Generate professional PDF reports
- **Ledger Management** - Organize invoices by ledger categories

### 🔐 Security & Access Control
- **Role-Based Permissions** - Fine-grained access control with Filament Shield
- **User Management** - Comprehensive user and admin management
- **Secure Customer Portal** - Protected customer access to invoices
- **Social Login Integration** - Google OAuth integration available
- **Multi-level Authentication** - Separate admin and customer authentication

### 🔧 Technical Features
- **Modern UI/UX** - Beautiful, responsive design with Tailwind CSS
- **Real-time Updates** - Live form updates and calculations
- **Search & Filtering** - Advanced search and filtering capabilities
- **Bulk Operations** - Perform bulk actions on invoices and customers
- **Data Validation** - Comprehensive form validation and error handling
- **Performance Optimized** - Efficient database queries and caching

---

## 🛠️ Technology Stack

### Core Framework
- **Laravel 11.x** - Modern PHP framework
- **PHP 8.2+** - Latest PHP version support
- **Filament 3.x** - Modern admin panel framework

### Frontend Technologies
- **Tailwind CSS 3.x** - Utility-first CSS framework
- **Vite** - Fast build tool and development server
- **PostCSS** - CSS processing with modern features
- **Axios** - HTTP client for API requests

### Key Packages & Integrations
- **barryvdh/laravel-dompdf** - PDF generation for invoices and reports
- **filament-shield** - Role-based access control and permissions
- **socialment** - Social authentication (Google OAuth)
- **maatwebsite/excel** - Excel import/export functionality
- **laravel-pail** - Real-time log monitoring
- **timokoerber/laravel-one-time-operations** - Database migration utilities

### Development Tools
- **Laravel Pint** - Code style fixer
- **PHPStan** - Static analysis tool
- **Pest PHP** - Modern testing framework
- **Laravel Debugbar** - Development debugging tools

---

## 📋 System Requirements

### Server Requirements
- **PHP**: 8.2 or higher
- **Composer**: 2.0 or higher
- **Node.js**: 16.0 or higher
- **NPM**: 8.0 or higher

### PHP Extensions
- OpenSSL PHP Extension
- PDO PHP Extension
- Mbstring PHP Extension
- Tokenizer PHP Extension
- XML PHP Extension
- Ctype PHP Extension
- JSON PHP Extension
- BCMath PHP Extension
- GD PHP Extension

### Database Support
- MySQL 5.7+
- PostgreSQL 10+
- SQLite 3.8.8+
- SQL Server 2017+

---

## 🚀 Installation Guide

### 1. Clone the Repository
```bash
git clone https://github.com/your-username/invoicing-system.git
cd invoicing-system
```

### 2. Install PHP Dependencies
```bash
composer install
```

### 3. Install Node.js Dependencies
```bash
npm install
```

### 4. Environment Configuration
```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 5. Configure Environment Variables
Edit the `.env` file and configure the following:

```env
# Application Settings
APP_NAME="Invoice Management System"
APP_ENV=local
APP_KEY=base64:your-generated-key
APP_DEBUG=true
APP_URL=http://localhost

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=invoicing_system
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Mail Configuration (for notifications)
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

# Social Login (Optional)
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URL="${APP_URL}/auth/google/callback"
```

### 6. Database Setup
```bash
# Create database tables
php artisan migrate

# Seed initial data
php artisan db:seed
```

### 7. Setup File Storage
```bash
# Create storage link for file uploads
php artisan storage:link
```

### 8. Setup Filament Shield (Role & Permissions)
```bash
# Generate all permissions for resources
php artisan shield:generate --all

# Create super admin user
php artisan shield:super-admin
```

### 9. Build Frontend Assets
```bash
# For development
npm run dev

# For production
npm run build
```

### 10. Start the Development Server
```bash
# Option 1: Laravel development server
php artisan serve

# Option 2: Complete development environment (recommended)
composer run dev
```

The application will be available at `http://localhost:8000`

---

## 🔧 Configuration

### Admin Panel Access
- URL: `http://localhost:8000/admin`
- Create admin user during setup or use the super admin created with Shield

### Customer Portal Access
- URL: `http://localhost:8000/admin` (customers login to same panel with restricted access)
- Customer accounts are created automatically when adding customers

### File Uploads
- Invoice logos and attachments are stored in `storage/app/public`
- Make sure to run `php artisan storage:link` to create the symbolic link

### PDF Configuration
- PDF generation is handled by DomPDF
- Custom PDF templates can be created in `resources/views/pdf/`

---

## 📖 Usage Guide

### Getting Started
1. **Access Admin Panel**: Navigate to `/admin` and login with your admin credentials
2. **Setup Billers**: Create your business profiles with logos and contact information
3. **Add Customers**: Create individual customers or customer groups
4. **Configure Ledgers**: Organize your invoices by creating ledger categories
5. **Create Invoices**: Start generating invoices for customers or groups

### Creating Your First Invoice
1. Go to **Invoices** → **Create New Invoice**
2. Select the **Ledger** category
3. Enter **Invoice Number** and dates
4. Choose **Biller** (your business profile)
5. Select invoice type (**Customer** or **Customer Group**)
6. Add **Invoice Items** with descriptions, quantities, and rates
7. Include any **Extra Charges** if needed
8. Add **Payment Details** and **Terms**
9. Save and generate PDF

### Managing Payments
1. Navigate to the **Invoices** section
2. Select an invoice to add payments
3. Record payment amounts and dates
4. System automatically calculates remaining balance
5. Export payment reports as needed

### Customer Portal
- Customers automatically receive login credentials
- They can view all their invoices and payment history
- Download PDF invoices and payment receipts
- Access is automatically restricted to their invoices only

---

## 🔐 Security Features

### Authentication & Authorization
- **Multi-level Access Control**: Separate admin and customer authentication
- **Role-based Permissions**: Granular permission system with Filament Shield
- **Secure Customer Portal**: Customers only see their own data
- **Social Login**: Optional Google OAuth integration

### Data Protection
- **Input Validation**: Comprehensive form validation
- **SQL Injection Protection**: Laravel's built-in protection
- **CSRF Protection**: Cross-site request forgery protection
- **Secure File Uploads**: Validated file uploads with size limits

---

## 📊 Reporting & Analytics

### Available Reports
- **Payment Reports**: Comprehensive payment analysis
- **Invoice Analytics**: Track invoice performance
- **Customer Reports**: Customer payment history
- **Biller Performance**: Multi-biller analysis

### Export Options
- **Excel Export**: All data can be exported to Excel
- **PDF Reports**: Professional PDF report generation
- **Custom Date Ranges**: Filter reports by date ranges
- **Bulk Operations**: Export multiple invoices at once

---

## 🧪 Testing

### Running Tests
```bash
# Run all tests
composer run pest

# Run tests with coverage
php artisan test --coverage

# Run specific test suite
./vendor/bin/pest tests/Feature/InvoiceTest.php
```

### Code Quality
```bash
# Run code style fixer
composer run pint

# Run static analysis
composer run phpstan

# Run all quality checks
composer run review
```

---

## 🔄 Development Workflow

### Available Scripts
```bash
# Development server with hot reload
composer run dev

# Code formatting
composer run pint

# Run tests
composer run pest

# Static analysis
composer run phpstan

# Complete code review
composer run review
```

### Database Management
```bash
# Reset database
php artisan migrate:fresh --seed

# Create new migration
php artisan make:migration create_your_table

# Create new model
php artisan make:model YourModel -m
```

---

## 🌐 Deployment

### Production Deployment
1. **Set Environment**: Change `APP_ENV=production` in `.env`
2. **Optimize Application**:
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   php artisan optimize
   ```
3. **Build Assets**: `npm run build`
4. **Set Permissions**: Ensure `storage/` and `bootstrap/cache/` are writable
5. **Configure Web Server**: Point to `public/` directory

### Server Configuration
- **Nginx/Apache**: Configure virtual host to point to `public/` directory
- **SSL Certificate**: Recommended for production environments
- **Database**: Use managed database service for production
- **File Storage**: Consider using cloud storage for file uploads

---

## 🤝 Contributing

### Development Setup
1. Fork the repository
2. Create a feature branch: `git checkout -b feature/amazing-feature`
3. Make your changes
4. Run tests: `composer run review`
5. Commit changes: `git commit -m 'Add amazing feature'`
6. Push to branch: `git push origin feature/amazing-feature`
7. Open a Pull Request

### Code Standards
- Follow PSR-12 coding standards
- Write comprehensive tests for new features
- Update documentation for any changes
- Use meaningful commit messages

---

## 📞 Support & Documentation

### Getting Help
- **Issues**: Report bugs on GitHub Issues
- **Discussions**: Join community discussions
- **Documentation**: Comprehensive docs available
- **Examples**: Sample code and tutorials

### Resources
- [Laravel Documentation](https://laravel.com/docs)
- [Filament Documentation](https://filamentphp.com/docs)
- [Tailwind CSS Documentation](https://tailwindcss.com/docs)

---

## 📄 License

This project is open-source software licensed under the [MIT License](LICENSE). You are free to use, modify, and distribute this software in accordance with the license terms.

---

## 🙏 Acknowledgments

This project builds upon the excellent [Larament Starter Kit](https://github.com/codewithdennis/larament) and integrates various open-source packages that make this comprehensive invoicing solution possible.

### Core Dependencies
- **Laravel Framework** - The foundation of this application
- **Filament PHP** - Beautiful admin panel framework
- **DomPDF** - PDF generation capabilities
- **Maatwebsite Excel** - Excel import/export functionality
- **Tailwind CSS** - Modern utility-first CSS framework

---

## 🚀 Roadmap

### Upcoming Features
- [ ] **Multi-currency Support** - Handle multiple currencies
- [ ] **Tax Management** - Advanced tax calculation and reporting
- [ ] **Email Notifications** - Automated invoice and payment notifications
- [ ] **API Integration** - RESTful API for third-party integrations
- [ ] **Advanced Analytics** - Enhanced reporting and dashboard analytics
- [ ] **Mobile App** - React Native mobile application
- [ ] **Stripe Integration** - Direct payment processing
- [ ] **Multi-language Support** - Internationalization

### Version History
- **v1.0.0** - Initial release with core invoicing features
- **v1.1.0** - Added customer groups and multi-biller support
- **v1.2.0** - Enhanced reporting and export capabilities
- **v1.3.0** - Security improvements and role-based permissions

---

**Made with ❤️ using Laravel and Filament PHP**
