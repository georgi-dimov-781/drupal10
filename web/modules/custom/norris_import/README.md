# Module documentation

# Setup & features

The following repository contains a Drupal 10 installation + a custom module for fetching data from external API into Drupal.

1. Installation

The module can be installed manually:
- navigate to Admin > Extends > Search for the module name > click Install

Or using drush:

drush en module_name

2. Instructions

After installation, there will be a several new menu entries created for the module. You can find them from Structure > Jokes Menu Lvl 1
The module comes with two forms - one for configuring the API endpoint URL and the other settings, and one for importing the data from the external API provider.
In the configuration form you can set the number of the nodes to be imported, as well as the API url, and the content type.
In the import form you can check the data previously stored in the setting form and then you can click import for the process to start.
After import is done, you will be able to see the imported nodes in admin/content.
For displaying the data there is a View created, which displays all the nodes. The view can be accessed from url /jokes-list.

The configuration for CT Joke, the fields for it, and the Jokes View are included in the module so you use drush cex after installation.