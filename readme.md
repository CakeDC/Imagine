# Imagine Plugin for CakePHP #

## Setup ##

### Add the external Imagine Lib ###

You need to init the git submodule of imagine

	git submodule update --init

Or get it from https://github.com/avalanche123/Imagine

Copy Imagine into the plugins vendor folder Vendor/Imagine, the root of the Imagine package should be inside this folder. Vendor/Imagine/README.md should be present if you placed the code correctly.

### Salt ###

You need to configure a salt for Imagine security functions.

	Configure::write('Imagine.salt', 'your-salt-string-here');

We do not use Security.salt on purpose because we do not want to use the same salt here for security reasons.

### Load plugin ###

Load the imagine plugin in your bootstrap file, remember to use plugin bootstrap, like this

    CakePlugin::load('Imagine' => array('bootstrap' => true));

## Imagine Helper ##

The helper will generate image urls with named params to get thumbnails or whatever else operation is wanted and a hashes the url.

The hash can be checked using the Imagine Component to avoid that people try to bring your page down by incrementing the size of a requested thumbnail to generate thousands of images on your server.

	$url = $this->Imagine->url(
		array(
			'controller' => 'images',
			'action' => 'display',
			1),
		array(
			'thumbnail' => array(
				'width' => 200,
				'height' => 150)));
	echo $this->Html->image($url);

### Special note for high traffic sites ###

You should *not* generate images on the fly on high traffic sites, it might get your server locked up because of the many many requests!

The first request will hit your server and start generating the image while others try to do that at the same time causing the site become locked up in the worst case.

It is better to generate the needed versions after an image was uploaded and if other versions are needed later, generate them by a shell script.

For this purpose there is a method in the ImagineBehavior that will turn the image operation array into a string, see ImagineBehavior::paramsAsFilestring();

Suffix your image with the string generated by this method to be able to batch delete a file that has versions of it cached. The intended usage of this is to store the files as my_horse.thumbnail+width-100-height+100.jpg for example.

So after upload store your image meta data in a db, give the filename the id of the record and suffix it with this string and store the string also in the db. In the views, if no further control over the image access is needed, you can simply link the image like `$this->Html->image('/images/05/04/61/my_horse.thumbnail+width-100-height+100.jpg');` directly.

## Imagine Component ##

The Imagine component does the following:

 * If configured for actions it validates automatically if a valid hash from ImagineHelper was passed as named param within the url
 * Gets the hash from the url: getHash()
 * Validates the has based on the named params: checkHash()
 * Automatically unpacks the packed named args: unpackParams()

## Imagine Behavior ##

The behavior interacts with the component and will process a given image file with a set of operations that should be applied to it. See ImagineBehavior::processImage().

### Imagine instance ###

Makes an Imagine instance available to the model. Get it by calling

	$this->imagineObject();

or directly through the behavior

	$this->Behaviors->Imagine->Imagine

## Caching and Storage ##

This plugin *does not* take care of how you store the images or how you cache them but it will offer you some helping methods for caching images based on a hash or a unique string.

This is a design decision that was made because everyone likes to implement the file storage a little different. So it is up to you how you store the generated images.

Support
-------

For bugs and feature requests, please use the [issues](https://github.com/burzum/cakephp-imagine-plugin/issues) section of this repository.

Contributing
------------

To contribute to this plugin please follow a few basic rules.

* Pull requests must be send to the ```develop``` branch.
* Contributions must follow the [CakePHP coding standard](http://book.cakephp.org/2.0/en/contributing/cakephp-coding-conventions.html).
* [Unit tests](http://book.cakephp.org/2.0/en/development/testing.html) are required.

License
-------

Copyright 2012 - 2014, Florian Krämer

Licensed under The MIT License
Redistributions of files must retain the above copyright notice.
