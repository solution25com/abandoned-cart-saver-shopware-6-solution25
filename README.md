# Abandoned Cart Saver
 
## Introduction
 
The Abandoned Cart Saver plugin helps Shopware store owners recover potentially lost sales by tracking abandoned shopping carts and allowing easy order creation from them. When customers leave items in their carts without completing a purchase, the plugin logs those carts and makes them accessible in the admin area for follow-up or order generation.
 
This tool is useful for customer service teams and sales staff to reach out and finalize purchases that might otherwise be missed.
 
### Key Features
 
1. **Abandoned Cart Tracking**
   - Monitors user carts and identifies carts abandoned after a configurable time.
2. **Admin Module for Abandoned Carts**
   - Adds a new section under Orders → *AdminOrders* displaying all abandoned carts.
3. **Customer Cart Overview**
   - Adds a new "Abandoned Cards" tab in the customer profile for detailed cart insights.
4. **Manual Order Creation**
   - Allows administrators to create an order directly from an abandoned cart.
5. **Scheduled Tasks**
   - Automatically updates and maintains abandoned cart data via scheduled Symfony tasks.

 
## Get Started
### Installation & Activation
1. **Download**
## Git
- Clone the Plugin Repository:
- Open your terminal and run the following command in your Shopware 6 custom plugins directory (usually located at custom/plugins/):
  ```
  git clone https://github.com/solution25com/abandoned-cart-saver-shopware-6-solution25.git
  ```
2. **Install the Plugin in Shopware 6**
- Log in to your Shopware 6 Administration panel.
- Navigate to Extensions > My Extensions.
- Locate the newly cloned plugin and click Install.
3. **Activate the Plugin**
- After installation, click Activate to enable the plugin.
- In your Shopware Admin, go to Settings > System > Plugins.
- Upload or install the “AdminOrders” plugin.
- Once installed, toggle the plugin to activate it.
4. **Verify Installation**
- After activation, you will see AdminOrders in the list of installed plugins.
- The plugin name, version, and installation date should appear as shown in the screenshot below.
 
  ![img1 1](https://github.com/user-attachments/assets/113b97e7-d604-473d-aacd-eb6ff29a2f4a)

 
## Plugin Configuration
 
After installing and activating the plugin, basic functionality works out of the box. However, you can configure how long the system waits before marking a cart as abandoned.
 
Configurable Timeout
Navigate to Extensions > My Extensions > AdminOrders > Configure.
 
Set the timeout period (in seconds) after which a cart is considered abandoned.
 
  ![img2 1](https://github.com/user-attachments/assets/ddf0f7bc-ab98-4a5b-94db-b499e273fa87)

 
## Run Required Commands
 
To ensure the abandoned cart tracking works as expected, run the following commands from the root of your Shopware 6 project:
 
```bash
bin/console scheduled-task:register
bin/console scheduled-task:run
bin/console messenger:consume
```
 
After running the last command, press `1` and hit **Enter** when prompted. This updates the database with the latest execution times and ensures scheduled tasks run properly.
 
 
## How It Works
 
1. **Customer Adds Items to Cart**
   - The system tracks all active carts.
 
2. **Cart is Abandoned**
   - If the customer leaves the cart without checking out, it appears in the *Abandoned Carts* page.
 
    ![img3 1](https://github.com/user-attachments/assets/77a1ac8e-7aab-4a4f-89c6-0b1b149e90a1)

 
3. **View and Manage Abandoned Carts from Admin**
   - Visit **Orders > AdminOrders** to see the cart list.
     ![img4 1](https://github.com/user-attachments/assets/30f7b0aa-8bf0-42aa-8823-441998e542c3)
 
   - View the line items clicking the three dots then View Line Items.
     ![img5 1](https://github.com/user-attachments/assets/dc49709e-e08b-4097-87ee-7f4c59e0b19f)

 
   - Click the **Create Order** button to convert a cart into an official Shopware order.
  

# Save Carts Plugin - Admin API Documentation

This document describes the custom Admin API endpoint provided by the Save Carts Plugin for Shopware 6. This endpoint allows authorized users to create an order from a customer's existing (abandoned) cart.

---

## Create Order from Abandoned Cart

**Endpoint:**  
`POST /api/_action/abandoned-cart/{customerId}/create-order`

**Description:**  
Creates an order from a saved cart associated with the given `customerId`.  
This endpoint is useful for:  
- Recovering abandoned carts  
- Manually or automatically triggering cart-to-order conversion  
- Admin-side marketing flows or automation tools

**Request Headers:**
- `Authorization: Bearer <your-access-token>`
- `Content-Type: application/json`

**Successful Response:**
```json
{
  "success": true,
  "message": "Order created successfully"
}
```

**Example Error Response:**
```json
{
  "success": false,
  "message": "Cart not found"
}
```

## Authentication

All endpoints require a valid **Admin API Bearer token**.  
You can obtain this token via the standard Shopware Admin API authentication process.

---

 
## Best Practices
 
- **Set an Appropriate Timeout Period**
   - Make sure the timeout period for marking a cart as abandoned aligns with your store's sales cycle.
- **Regularly Monitor Abandoned Carts**
   - Make it a routine to check the Abandoned Carts section under Orders > AdminOrders.
 
- **Regularly Monitor Abandoned Carts**
   - Make it a routine to check the Abandoned Carts section under Orders > AdminOrders.
- **Communicate with Customers**
   - In addition to creating orders, reaching out to customers whose carts have been abandoned can help                personalize the shopping experience and improve customer retention.

 
## Troubleshooting
 
- **Carts Not Being Marked as Abandoned**
   - If carts aren't being marked as abandoned after a period of inactivity, check the plugin configuration. 
- **Plugin Not Loading or Showing Errors**
   - If the plugin is not loading correctly or showing errors in the Admin interface, it could be due to               installation issues.
- **Customer Profile Not Showing Abandoned Cart Tab**
   - If the "Abandoned Cart" tab is missing in the customer profile, verify that the plugin is correctly               installed and activated.
- **Scheduled Tasks Not Running**
   - If the plugin isn’t updating or processing abandoned carts as expected, the scheduled tasks may not be            running correctly. Ensure the scheduled tasks are registered and running by executing the following commands:
     ```bash
     bin/console scheduled-task:register
     bin/console scheduled-task:run
     bin/console messenger:consume
     ```
 
 
## FAQ
 
- **How long before a cart is marked abandoned?**
   - By default, after 300 seconds of inactivity.
 
- **Can I customize the timeout period?**
   - Yes, you can configure the timeout value through the plugin settings in the admin panel.
 
- **What data is stored for each cart?**
   - The plugin logs the customer's name, cart total, number of items, creation time, and individual line items.

 
