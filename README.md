# Verified House Kenya

A PHP and MySQL-based property verification and management system designed to help users search, manage, and verify properties online.

## Overview

Verified House Kenya is a web-based property management platform built with PHP and MySQL.

The system provides separate functionality for:

- Tenants
- Landlords
- Administrators

It includes property management, property verification, viewing requests, tenant bookings, favourites, notifications, and administrative management.

## Features

### Tenant

- User registration and login
- Search available properties
- View property details
- Add properties to favourites
- Request property viewings
- Manage bookings
- Receive notifications

### Landlord

- Landlord dashboard
- Add properties
- Edit property information
- Upload property images
- Manage listed properties
- Manage viewing requests
- Delete property images

### Administrator

- Admin dashboard
- Property management
- Property verification
- User management
- Viewing request management
- Reports
- System settings

## Property Verification

The system provides an administrative verification workflow where submitted properties can be reviewed and managed by administrators before being treated as verified listings.

## Technologies

- PHP
- MySQL / MariaDB
- HTML5
- CSS3
- JavaScript
- Bootstrap
- XAMPP
- Git & GitHub

## Project Structure

```text
verified-house-kenya/
│
├── admin/
│   ├── dashboard.php
│   ├── properties.php
│   ├── reports.php
│   ├── settings.php
│   ├── users.php
│   ├── verification.php
│   └── viewing-requests.php
│
├── assets/
│   └── images/
│
├── database/
│   └── database.example.sql
│
├── includes/
│   ├── auth.php
│   └── db.php
│
├── landlord/
│   ├── add-property.php
│   ├── dashboard.php
│   ├── delete-property-image.php
│   ├── edit-property.php
│   ├── my-properties.php
│   └── viewing-requests.php
│
├── tenant/
│   ├── bookings.php
│   ├── dashboard.php
│   ├── favourites.php
│   └── request-viewing.php
│
├── index.php
├── login.php
├── logout.php
├── notifications.php
├── property.php
├── register.php
└── search.php