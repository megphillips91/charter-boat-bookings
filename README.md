# Charter Boat Bookings #
Contributors: megphillips91

Donate link: https://msp-media.org/product/support-open-source/

Tags: sailing charter reservations, fishing charter bookings, WooCommerce boat booking system, boat reservation software, online boat booking, boat charter software, charter boat booking software, charter booking software

Requires at least: 5.1

Tested up to: 5.9.2

Requires PHP: 5.6.4

Stable tag: trunk

WC requires at least: 5.7

WC tested up to: 6.2

License: GPLv2 or later

License URI: http://www.gnu.org/licenses/gpl-2.0.html


Charter Boat Bookings is a boat booking system for captains. Use Woocommerce for reservations - Sunset Sails, Daysails, Sportfishing, Inshore Fishing

## Description ##

### Summary ###
Charter Boat Bookings is a WooCommerce extension specifically designed to take reservations for Charter Boats. Built to suit the business needs of Sailing Charters, Fishing Charters, Sportfishing, Fishing Guides, even Duck Hunting Guides. This WordPress charter boat booking system includes industry specific features including weather predictions and sunset dependent products. Book private or per person charters, and set your maximum passenger capacity. Minimize refunds with built-in reservation fee and final balance.

This plugin works as boat reservation software. It is not a general reservation system for tour operators that has been adapted into an online boat booking system. The target users for this plugin are owner-operator captains with no code writing expertise. The dashboard has as few settings as possible to set up a typical sailing or fishing schedule and take paid reservations, manage availability, and communicate effectively with your customers.

## Changelog ##
### March 19-2022 ###
- Meg rebased from svn trunk on Mar-19,2022 due to plugin suspension for potential sql injection vulnerability
- Please review the changes to the CB_Booking_Query Class which is used by the availabilty, charter-confirmation, admin ajax calls to query the bookings from custom database table wp_cb_bookings
- Added method args_are_valid which returns true or false on checking data type
- used $wpdb->prepare within the query factory methods to prepare the query and then pass that back into the class and run the query on instantiation.
- if you have any questions, please feel free to reach out to me on issues tab here. 
- cheers :)



#### Background ####
Developed by a team with more than 40 combined years experience within all aspects of the charter industry: daysailing, bareboat charters, inshore fishing charters, sportfishing charters, marina management, and even fleet management.

Designed to scale with your business from the ground up, Charter Boat Bookings is offered in two versions: Charter Bookings Lite and [Charter Bookings Pro](https://msp-media.org/wordpress-plugins/charter-bookings/). The free version available here will support a single boat in day charter business - fishing, sailing, or pleasure trips. The premium version offers more features and flexibility. Please check it out.

#### Features ####
**Weather Prediction:** Product listings and single product pages connect with the OpenWeather API so that they can show accurate wind and weather prediction for up to five days in the future. The weather is volatile, and the charter boat industry is depends on the weather. Refunds are potentially the most challenging aspect of charter boat business management. Showing your customers the weather conditions and wind speed/direction before they book helps naturally shift customers into a better customer experience and minimize refunds.

**Sunset Products:** Create sunset sails or sunset fishing trips that dynamically adjust to your location’s actual sunset time by connecting with the Sunrise-Sunset API. Sunset products calculate start time by backing out the duration of the charter from civil twilight so that you are back at the dock safely before dark, but your guests enjoy the most beautiful sunset colors the sky has to offer.

**Minimize Refunds With Two Payments:** When your business is weather dependent and advance booking deposits are the norm, refunding deposits due to bad weather can become a real headache. The customer demands associated with refunds can quickly cut into your cash flow and potentially lead to sticky situations that could ruin your reputation.

Charter Boat Bookings helps you better manage your cash flow by splitting the total charter fee into a smaller up-front reservation payment and a larger final payment. The product settings allows you to control the total cost of the payments as well as the up-front reservation payment. The system then automatically sets the due date for the final payment to 3 days prior to the charter and notifies your customer to pay the final balance.  Three days is a carefully considered window: weather predictions usually become accurate 3 days prior to the charter, plus most credit-card merchant processors take 3 days to deposit credit card payments into your bank account. In short, the Charter Boat Bookings plug-in helps you better manage your exposure to weather-related refunds.


**Manage Orders:** Each booking links to the reservation and final balance order within the bookings admin menu. This helps you administrate charter booking orders just as you would any other WooCommerce Order.

**Of course it does that:**
* unlimited bookings
* double bookings or overbooking is not allowed and charters that are not available cannot be purchased
* shows availability calendar
* customer balance due reminder notification
* weather forecast shows within product listing and 'book now'
* forecast considers the charter start time and renders the closest forecast (accurate within 3hrs)
* forecast displays wind speed and direction
* charter durations can be 4hr or 8hr
* sunset charters adjust start time based on duration set and arrival back to dock at civil twilight
* availability dynamically considers the start time of sunset charters

Please consider upgrading to [Charter Bookings Pro](https://msp-media.org/wordpress-plugins/charter-bookings/) version which offers many more features you may find useful in your business.

### Installation ###

#### INSTALLING THE BOOKING PACKAGE PLUGIN IS EASY ####
1. From the dashboard of your site, navigate to Plugins –> Add New.
2. Select the Upload option and hit “Choose File.”
3. Follow the on-screen instructions and wait as the upload completes.
4. When it’s finished, activate the plugin via the prompt. A message will show confirming activation was successful.

### Frequently Asked Questions ###
<details>
   <summary>How do I get hooked up to process credit cards?</summary>
   <p>To accept credit cards, you will need a merchant processor (also known as Payment Gateway). We recommend Stripe or Square. They are both easy enough, not too expensive, and work great with WooCommerce.</p>
   <p>Once your account with the merchant is set up and active, navigate to WooCommerce->settings->payments and enable the payment gateway that you chose.</p>
</details>
<details>
   <summary>What if I want to take cash at the dock?</summary>
   <p>To accept cash at the dock, you enable cash or check payments within WooCommerce -> Settings -> Payments ->enable cash on delivery.</p>
</details>
<details>
   <summary>Are charters products available each day?</summary>
   <p>In short, yes. Charter Boat Bookings is based on the most common business model which is fishing or sailing any day that a customer wants to go out and the weather is good. So we’ve designed the settings to be as few and simple as they can be to meet that business model. When you create a charter booking product, it becomes available for booking every day.</p>
   <p>If you are running a business and rely on the charter income, you probably need to upgrade to the Owner Operator Version of Charter Boat Bookings which includes the option to be open on some days and closed on others. In the Premium Owner Operator Version, navigate to WooCommerce->settings->product-> Charter Bookings and set the days you are open.</p>
</details>
<details>
   <summary>Why do I need more than one charter product?</summary>
   <p>Many fishing captain’s offer half day and whole day charters. Sailing captains usually offer half day, whole day, and sunset sails. You will set up each of these as a product within WooCommerce. If you offer per person and private, then you would set up each of those also - one for private and one for per person.</p>
   <p>An example of a common Sailing Product Assortment may be:
      * Morning Half Day
      * Morning Half Day Per Person
      * Afternoon Half Day
      * Afternoon Per person
      * Sunset
      * Sunset Per Person
      * Private Whole Day Sail
   </p>
</details>
<details>
   <summary>Why would I want to offer Per Person Charters instead of only Private Charters?</summary>
   <p>Charter Boat Bookings plugin is set up to require a minimum number of seats sold within the first booking, so the risk is pretty low.  If you set the per-person rate higher than the pro-rata private rate for the same charter, the overall revenue potential is higher with per person charters.</p>
  <p>We’ve found that it helps grow your business by advertising “charters starting at $x.xx) which is the per person rate for the shortest charter. This way of speaking about your charter offering grows your audience by including customers who may wrongly assume a charter is out of their budget. </p>
</details>
<details>
   <summary>How do I set up a Charter on the calendar?</summary>
   <p>Charter Boat Bookings follows the standard WooCommerce workflow. Setting up charters is the same as creating any other product in WooCommerce. Check out these links within the WooCommerce Documentation
    * [WooCommerce Getting Started](https://docs.woocommerce.com/documentation/plugins/woocommerce/getting-started/)
    * [WooCommerce Setting Up Products](https://docs.woocommerce.com/documentation/plugins/woocommerce/getting-started/setup-products/)
    </p>
    <p>There are just a few extra fields for a charter booking which you can see within the screenshots.
      * Reservation fee
      * Final balance
      * Location
      * Sunset Charter?
      * Charter Start Time
      * Duration
    </p>
</details>
<details>
   <summary>I followed the directions from WooCommerce, but the Calendar and product listing looks all jacked up and nothing like the screenshots you show. What did I do wrong?</summary>
    <p>The charter product listing needs about 850px in minimum width for the desktop display. If your theme restricts the content area to less than that, you will need to make a theme revision with a little custom CSS to expand the content width. </p>
    <p>If the headlines are too large on the Product Listing and Availability calendar, you will need to make a theme revision with a little custom CSS for charter bookings single product pages. </p>
</details>
<details>
   <summary>Can I change the final balance due date? </summary>
    <p>The settings within the product admin screen allow you to set the amount of the final balance. The due date timing is static in Charter Bookings Lite at 3 days prior. It can be shifted, notifications cancelled in [Charter Bookings Pro](https://msp-media.org/wordpress-plugins/charter-bookings/).</p>
</details>
<details>
   <summary>How much should I set for the reservation fee vs. the charter fee?</summary>
    <p>This is really a business decision, but we’ve learned from experience that 15% is a good starting point. This proportionately scales the reservation cost with the overall cost of the product and aligns with industry standard brokerage fees and booking commissions. Keep in mind that any amount paid up front for a reservation creates a psychological buy-in for the customer. Collecting final balance payments is not usually a problem as long as the weather is good.</p>
    <p> Our best recommendation is to use your gut and balance your priorities. If you find that holding enough reserves for large refunds is not a challenge, then you could set the reservation fee higher around 50%.</p>
</details>


== Screenshots ==

1. Product Page for a charter: above the fold what your customer sees when they are considering a reservation.
2. Product Page for a charter: below the fold, a new "Book Now" tab is added to the WooCommerce tabs
3. Settings: Find plugin settings located within WooCommerce->Settings->Products->Charter Bookings
4. Settings: Vessel Settings
5. Settings: Location Settings
6. Product Admin: Create product - Select Charter Booking product type
7. Product Admin: Create Product - Complete charter Settings
8. Bookings Admin Menu: Located within WooCommerce->Bookings

== Changelog ==

= 1.0 =
* This is the initial release
