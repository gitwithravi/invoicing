
# 🧾 Filament Invoicing and Payment System

The **Filament Invoicing and Payment System** is a Laravel-based web application built with [Filament Admin Panel](https://filamentphp.com/). It allows administrators to generate and manage invoices for customers. Customers can securely log in to view and download their invoices and payment receipts.

---

## ✨ Key Features

- **Multi-Biller Support**  
  Create and manage multiple billers. Each invoice can be generated under any biller profile.

- **Customer Groups**  
  Organize customers into groups. Invoices created for a group are automatically accessible to all customers within that group.

- **Automatic Customer Account Creation**  
  No manual setup needed—when a new customer is added, a login account is created automatically.

---

## 🧩 Technologies & Packages Used

This project builds on top of the [Larament Starter Kit](https://github.com/codewithdennis/larament), which includes all core Filament features and packages. In addition, it integrates:

- [laravel-dompdf](https://github.com/barryvdh/laravel-dompdf) – for generating PDF invoices and receipts  
- [filament-shield](https://github.com/bezhanSalleh/filament-shield) – for role-based access control  
- [socialment](https://github.com/chrisreedio/socialment) – for social login functionality

---

## 🚀 Installation Guide

### 1. Clone the Repository

```bash
git clone <your-repository-url>
cd <your-project-folder>
```

### 2. Install Dependencies

```bash
composer install
npm install && npm run build
```

### 3. Setup Environment File

```bash
cp .env.example .env
php artisan key:generate
```

### 4. Run Migrations and Seeders

```bash
php artisan migrate
php artisan db:seed
```

### 5. Setup Filament Shield (Optional, Recommended)

```bash
php artisan shield:generate --all
php artisan shield:super-admin
```

> ✅ Select the user you want to make the Super Admin during the last command.

---

## 🔐 User Management

- **Admins & Roles**: Use Filament Shield to manage admin roles and permissions.
- **Customer Accounts**: Created automatically when a customer is added to the system.
- **Customer Access**: Customers can only log in if their account already exists. No manual login creation is necessary.

---

## 📄 License

This project is open-source and available under the [MIT license](LICENSE).
