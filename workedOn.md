# Amelia No-Show Tracker Plugin Workflow

## Overview
The Amelia No-Show Tracker is a WordPress plugin that helps manage and track no-show appointments in the Amelia booking system. It provides a user-friendly interface for staff to monitor, manage, and resolve no-show situations.

## Main Components

### 1. Plugin Structure
- `no-shows.php`: Main plugin file that initializes everything
- `class-no-show-tracker.php`: Core functionality handler
- `admin-page.php`: Admin interface template
- `admin.js`: Frontend interaction logic
- `admin.css`: Styling for the interface

### 2. Database Tables
The plugin creates two custom tables:
- `amelia_no_show_penalties`: Tracks current penalties for each customer
- `amelia_no_show_history`: Records the history of resolved no-shows

## How It Works

### Initial Setup
1. When the plugin is activated, it:
   - Creates necessary database tables
   - Sets up menu items in the WordPress admin
   - Integrates with existing Amelia booking system

### Main Interface
1. Staff members access the plugin through:
   - WordPress Admin Panel → Amelia → No-Shows

2. The main screen shows:
   - List of customers with no-show records
   - Number of no-shows per customer
   - Current penalty amounts
   - Action buttons for management

### Viewing No-Show Details
1. Click "View Details" for any customer to see:
   - Dates of missed appointments
   - Services that were missed
   - Provider names
   - Options to resolve each no-show

### Resolving No-Shows
1. When marking a no-show as paid:
   - Staff clicks "Mark as Paid" button
   - System updates the appointment status
   - Recalculates penalty amounts
   - Records the resolution in history
   - Updates the display automatically

### Behind the Scenes
1. Data Management:
   - Automatically tracks no-shows from Amelia's booking system
   - Calculates penalties based on number of no-shows
   - Maintains history of all resolutions
   - Updates in real-time when changes are made

2. Integration:
   - Works seamlessly with existing Amelia tables
   - Uses WordPress's built-in AJAX system
   - Follows WordPress security practices
   - Responsive design for all screen sizes

## Technical Flow

1. Data Loading:
   - Page loads → AJAX request for no-show data
   - System queries multiple tables to gather information
   - Data is formatted and displayed in the main table

2. Customer Details:
   - Click on customer → AJAX request for detailed information
   - Modal opens with appointment history
   - Real-time updates when changes are made

3. Resolution Process:
   - Mark as paid → AJAX request to update status
   - System updates multiple tables:
     * Changes appointment status
     * Updates penalty calculations
     * Records in history
   - Interface refreshes to show current data

## Security Features
- WordPress nonce verification for all AJAX requests
- Data sanitization and validation
- Proper user capability checks
- Secure database operations

## User Experience
- Clean, intuitive interface
- Real-time updates
- Confirmation dialogs for important actions
- Loading indicators for feedback
- Responsive design for all devices 