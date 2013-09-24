# WP Front-End Media Upload (FEMU)

Create simple image uploads with preview in your WordPress themes and store them in the media gallery.

## Installation

To install the plugin clone the repo in a folder in your plugins directory, ie. `wp-content/plugins/femu` and then activate it through WordPress.

Then you need to create a temporary folder in `/wp-content/uploads/temp` and set the right permissions (or just `chmod 777`). All succesful and failed uploads will be put here so FEMU can work its magic. It's recommended that you set-up a cron job to delete these files periodically.

## How to

First create you main form:

```html
<form id="main-form" action="post">
  <label>Name:</label><input type="text" name="name">
  <label>E-Mail:</label><input type="email" name="email">
  <button type="submit">Submit</button>
</form>
```

Then **outside** the main form call the plugin to build the image preview field:

```php
...
</form>

<?php FrontEndMediaUpload::form('field_name', array(
  'dimensions' => array(250, 250),
  'extensions' => array('jpg', 'png')
)) ?>
```

The above will create form with an file input and a preview area. The `'field_name'` will be used as id and name of the file input.

FEMU will print a detailed error in the preview area if the dimensions or extensions don't validate. FEMU will proportionally scale and crop bigger images to match the specified dimensions. If something goes wrong with the transfer, it will print a generic error. 

Once you have the markup set-up, you need to link the file to the main form. FEMU provides provides a jQuery plugin to do this quickly on submit:

```javascript
$('#main-form').submit(function(){
  $(this).frontEndMediaUpload('#field_name');
});
```

If you have multiple FEMU forms in your page just add them to the selector:

```javascript
$(this).frontEndMediaUpload('#field1, #field2, #field3');
```

Now, when you submit the form, FEMU will add the a hidden field to your main form with the image's filename only if the image upload was succesful:

```html
<form id="main-form" action="post">
  <label>Name:</label><input type="text" name="name">
  <label>E-Mail:</label><input type="email" name="email">
  <input type="hidden" name="wp-femu-attachment-field_name" value="user_5240d16add04d-test.jpg">
  <button type="submit">Submit</button>
</form>
```

Finally, when you POST the form and read the hidden field you can add the image to the media gallery:

```php
// Validate all other data first...

// Add image to WordPress' media gallery and get the attachment ID
$attachmentID = FrontEndMediaUpload::upload($_POST['wp-femu-attachment-field_name']);
```
