=== Truefy Embed ===
Contributors: truefy
Donate link: https://www.truefy.ai/
Tags: image, images, picture, photo, watermark, watermarking, protection, image protection, image security
Requires at least: 5.8.4
Tested up to: 5.9.3
Stable tag: 1.1.0
Requires PHP: 7.0
License: GNUGPLv3
https://www.gnu.org/licenses/gpl-3.0.html

Truefy Embed's proprietary AI invisible watermarking technology secures your images by allowing you to add critical details directly into the pixels.

== Description ==

[Truefy Embed](https://embed.truefy.ai/) allows you to invisibly watermark images uploaded to the WordPress Media Library or a WordPress Post.
The Plugin will also directly sync the changes in Image Caption from WordPress to Truefy Embed
Once uploaded, you can view them in the Image library in Truefy Embed Website, where you can add or edit the critical details like Image Capture Datetime, Publisher Details etc.

For more information, check out   [plugin page](https://embed.truefy.ai/integrations/wordpress) or email us at support@truefy.ai

You can check Truefy Embed's Terms of Service at [ToS Page](https://embed.truefy.ai/legal/tos)

= Features include: =

* Auto Invisible Watermarking - Images uploaded to Media Library or a Post can be  automatically invisibly watermarked
* Manual Invisible Watermarking/De-Watermarking - Images can be manually watermarked, and even be de-watermarked
* Image Caption Syncing - Captions added for Images in Media Library or Post Caption, are directly synced to Truefy Embed Servers


== Installation ==

1. Install Truefy Embed either via the WordPress.org plugin directory, or by uploading the files to your WordPress server
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to the Truefy Embed menu in Settings and set your watermarking options.
4. You can find your API Key on the Truefy Embed Website [Settings Page](https://embed.truefy.ai/settings/integrations).
If you don't already have a Truefy Embed account, you can request for a demo account by visiting this [Request Demo Url] (https://embed.truefy.ai/company/contact?subject=requestDemo)
5. Copy your API Key to the "Truefy WordPress API key" Field. You can also set "Auto Watermarking Enabled" to "yes", to turn on Auto Invisible Watermarking
6. Click on "Update Options" button to save the changes

== Frequently Asked Questions ==

= How can I manually watermark? =
Go to the Media Library, select an Image, click on the "Edit more details" (it's on right of "Delete Permanently"), inside there you will see
a MetaBox with the title "Truefy Watermark". If the Image has no watermark present, it will allow to you "Apply Watermark", and incase of already
existing watermark, it will allow you to "Remove Watermark". To execute the operation, click on the CheckBox, and click on the "Update" button of the Save MetaBox

= How to stop Auto Watermarking =
Go to the Truefy Embed menu in WordPress Settings, set "Auto Watermarking Enabled" option to "No", and click on "Update Options" button

= Do I need a Truefy Embed Account for it? =
Yes, you need a Truefy Embed Account to use the Wordpress Plugin. If you don't already have a Truefy Embed account,
you can request for a demo account by visiting this [Request Demo Url](https://embed.truefy.ai/company/contact?subject=requestDemo)

= How to get the Truefy Embed API Key? =
Please visit the Settings Page of Truefy Embed Website [Settings Page](https://embed.truefy.ai/settings/integrations) to find your API Key

= What does caption syncing mean? =
Whenever you add an Image Caption either from the Media Library or the WordPress Post, the same caption is sent to Truefy Embed Servers and used
as a critical detail for the given image

= How can I add my Brand Logo to Images? =
Firstly, go to Truefy Embed [Integration Settings Page](https://embed.truefy.ai/settings/integrations) and add your logo image and default settings
like logo location,logo opacity, logo resize factor etc. After that in the WordPress Truefy Embed Plugin Settings, set "Automatically Add Visible Brand Watermark to Images"
to "yes", now all your newly uploaded images will have your Brand Logo.

= How to modify the brand logo in an Image? =
If an Image already has the brand logo, and you want to modify some settings like (logo location).
Go to the Media Library, select an Image, click on the "Edit more details" (it's on right of "Delete Permanently"), inside there you will see
a MetaBox with the title "Truefy Watermark". Check the "Modify/Add Visible Watermark" option and change the watermark settings as per your need, and click on the "Update" button of the Save MetaBox

== Screenshots ==

1. screenshot-1.png
2. screenshot-2.png
3. screenshot-3.png

== Changelog ==

= 1.0.0 =
Initial release
= 1.0.1 =
Added Icon and Fix Issued of stale thumbnails after manual watermark
= 1.0.2 =
Performance improvement - One API call for multiple images while saving post
= 1.0.3 =
UX Improvements, showing image ml model version
= 1.1.0 =
Added Visible Brand Logo Watermark feature
UX Improvements, showing image ml model version
== Upgrade Notice ==

= 1.0.0 =
Initial Release

= 1.1.0 =
Added Visible Brand Logo Watermarking