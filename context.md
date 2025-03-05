# Amelia No-Show Tracker Plugin Context

## Overview
The Amelia No-Show Tracker plugin is designed to manage and track no-show appointments in the Amelia booking system. The plugin works with Amelia's core tables to track appointments, identify no-shows, and manage related data.

## Core Tables and Their Relationships

### 1. Amelia Core Tables
- `amelia_customer_bookings`: Central table for no-show tracking with key columns:
  - `id`: Unique identifier for the booking
  - `appointmentId`: Reference to the appointment
  - `customerId`: Reference to the customer
  - `status`: Appointment status which can be:
    - approved: Confirmed appointments
    - pending: Awaiting confirmation
    - canceled: Canceled by customer or staff
    - rejected: Rejected by staff
    - no-show: Customer didn't attend
    - waiting: On waiting list

- `amelia_appointments`: Manages appointment details:
  - `id`: Unique identifier referenced by customer_bookings
  - `bookingStart`: Date and time of the appointment
  - `serviceId`: Reference to the service being provided
  - `providerId`: Staff member providing the service
  - Note: One appointment can have multiple customer bookings (group booking)

- `amelia_services`: Contains service information:
  - `id`: Unique identifier for the service
  - `name`: Service name (e.g., class types like "Sesión de Barre", "Sesión de Pilates")
  - `description`: Detailed service description with HTML formatting

- `amelia_users`: Central user management table with key columns:
  - `id`: Primary identifier used throughout Amelia
  - `type`: User type identifier (e.g., "customer" for clients)
  - `externalId`: Reference to WordPress user ID (if linked)
  - `firstName`: User's first name
  - `lastName`: User's last name
  - `email`: User's email address
  - `phone`: Contact number

- `amelia_packages`: Contains service package information:
  - `id`: Unique identifier for the package
  - `name`: Package name (e.g., class bundles)
  - `description`: Detailed package description and inclusions
  - `color`: Color code for UI display
  - `price`: Package cost
  - `status`: Package availability (e.g., visible, hidden)

- `amelia_packages_to_services`: Junction table linking packages to services:
  - `id`: Unique identifier
  - `serviceId`: Reference to service in amelia_services
  - `packageId`: Reference to package in amelia_packages
  - `quantity`: Number of services included in the package

### 2. Key Table Relationships

#### Primary No-Show Tracking Flow:
- `amelia_customer_bookings` is the source of no-show status
- Links to `amelia_appointments` for appointment details
- Links to `amelia_services` through appointments for service information
- Links to `amelia_users` for customer and provider details

#### Package Management Flow:
- `amelia_packages` defines available service bundles
- `amelia_packages_to_services` connects packages to their included services
- Links to `amelia_services` for specific service details

### 3. Plugin-Specific Tables

#### `amelia_no_show_penalties`
- Primary table for tracking customer penalties
- Links to customers through `customerId`
- Stores:
  - Total number of no-shows per customer
  - Total penalty amount
  - Last update timestamp
- Used for:
  - Quick access to customer penalty status
  - Displaying penalty information in admin interface
  - Calculating total penalties across all customers

#### `amelia_no_show_history`
- Historical record of penalty payments
- Links to customers through `customerId`
- Stores:
  - Customer details (name, email)
  - Number of no-shows at time of payment
  - Total penalty amount paid
  - Payment date
- Used for:
  - Tracking payment history
  - Maintaining audit trail
  - Reporting purposes

## Functional Workflow

### 1. No-Show Retrieval and Display
- System queries `amelia_customer_bookings` to find entries with status "no-show"
- For each no-show entry:
  - Joins with `amelia_users` to get customer information
  - Creates a table row with customer details
  - Each row represents a unique customer with no-shows

### 2. Individual No-Show Details (Dropdown)
- When dropdown is activated for a customer:
  - Queries `amelia_customer_bookings` for all "no-show" status entries for that `customerId`
  - Joins with `amelia_appointments` to get appointment dates
  - Joins with `amelia_services` to get service names
  - Displays each no-show appointment with its details

### 3. No-Show Resolution ("Cobrar")
- When "Cobrar" button is clicked for a specific appointment:
  - Updates `amelia_customer_bookings` table
  - Changes status from "no-show" to "approved" for the specific appointmentId
  - No additional tables need to be modified for this core functionality

### 4. Data Integrity
- All no-show information is sourced directly from `amelia_customer_bookings`
- Appointment details are retrieved through table relationships
- Status changes are handled through direct updates to `amelia_customer_bookings`

## Business Logic
- Penalties increase with each no-show
- Admin can forgive no-shows to reduce penalties
- System maintains complete history of all actions
- Real-time updates ensure current penalty status
- Filtering and sorting capabilities for easy management

## Development Instructions for Cursor AI

### 1. Core Files Structure
Create the following essential files:
- `no-shows.php`: Main plugin file with plugin header and initialization
- `includes/models/class-no-show-appointment.php`: Handles appointment data and status changes
- `includes/views/class-no-show-admin-view.php`: Manages admin interface rendering
- `includes/controllers/class-no-show-admin-controller.php`: Handles AJAX and admin actions
- `includes/assets/js/no-show-admin.js`: Frontend interaction logic
- `includes/assets/css/no-show-admin.css`: Styling for admin interface

### 2. Database Interaction
When writing queries:
- Use `amelia_customer_bookings` as the primary table for no-show status
- Join with `amelia_appointments` for date/time information
- Join with `amelia_services` for service details
- Join with `amelia_users` for customer and provider information
- Always use WordPress's `$wpdb->prefix` for table names
- Implement proper sanitization and escaping

### 3. Core Functionality Implementation
Focus on these key features:
1. **No-Show Listing**
   - Query `amelia_customer_bookings` with status = 'no-show'
   - Group by customer for the main table
   - Include all necessary joins for complete information

2. **Dropdown Details**
   - Show individual no-show appointments per customer
   - Display date, service, and instructor information
   - Include "Cobrar" button for each entry

3. **Status Update**
   - Update appointment status from 'no-show' to 'approved'
   - Refresh the UI after status changes
   - Handle edge cases (e.g., when all no-shows are cleared)

