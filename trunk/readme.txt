=== Podio For Contact Form 7 ===
Contributors: bigideaguy
Tags: Podio, Contact Form 7, API
Requires at least: 3.0
Tested up to: 4.2
Stable tag: 1.2
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This is a wordpress plugin for contact form 7 which will let you send the data from your contact forms directly to your Podio app.

== Description ==

This is a wordpress plugin for contact form 7 which will let you send the data from your contact forms directly to your Podio leads app or basically any other app. Simply add your API details and define which field should be your notes field and it will automatically start sending any submissions directly to Podio. Fields are matched by the contact form 7 field name and the external id in podio, any fields which go unmatched are sent to the notes field as to not loose any information.

This is only made to work with Contact Form 7.

Please note that nothing will be sent to podio unless all your API details are filled in. Use the external id of a notes field to catch any fields that do not match between CF7 and Podio.

== Podio API Details ==

To get your API details you will need to go to https://podio.com/settings/api?force_locale=en_US

Then you need to find the numeric id of the application. Navigate to your newly installed app inside Podio, click the little wrench next to the application name in the left sidebar and click on the 'Developer' option in the menu. On the following page the application id is listed along with other useful data.

The podio app url is formatted like this 'https://podio.com/workspace/appname/'

I use the simple lead handling example app to get this to work.

== Podio Debugging ==

The debugging function is there to help users solve any typ of issue that may arise.

Fields must be of the type location or text. This plugin does not support contact ids or sales agent ids for example. This may be something that could be implemented in future versions.

In this version I have made it so that an error will display as Error! - (error infor from podio)

If everything seems to be working the debugging information will display all the fields from the app you have configured podio to use. The names of these fields should match the names in your contact form 7 configuration as this addon simply tries to match fields of the same name and input it into your Podio app. You should also have an extra field under "Extra info field external id", I typically make this the "notes" field, any information not matched will be stored in this field.

If debugging is enabled then any Podio errors will be appended to the message body of the Podio message.

== Development ==

You will find the official Github repository here https://github.com/markustenghamn/Podio-For-Contact-Form-7

== Installation ==

1. Upload `podio-contact-form-7` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Go to the Podio CF7 settings page via the admin area and fill in your Podio API and app details. Once these are filled in and an id for your external notes field is set you should see information going directly from your contact form to Podio.