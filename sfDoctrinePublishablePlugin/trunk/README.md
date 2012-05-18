sfDoctrinePublishablePlugin
===========================

Introduction
------------

A plugin which adds Publishable behaviour to a model.  This behaviour adds 3 fields to a model - `published_at`, `publish_until` and `is_draft`.
The plugin also includes preDQL which can be enabled in an application's configuration to automatically filter models based on whether they are 
currently published or not.

Dependencies
------------

 * Doctrine 1.x
 * Symfony 1.3/1.4

Setup
-----

### 1. Enable the plugin

In `ProjectConfiguration`:
  
    class ProjectConfiguration extends sfProjectConfiguration
    {
      public function setup()
      {
        $this->enablePlugin('sfDoctrinePlugin','sfDoctrinePublishablePlugin');
      }
    ...

### 2. Enable preDQL callback in application configuration

All queries in this application for the models with Publishable behaviour will be restricted to those that are not draft, and whose publish dates
are valid for the current date.

In ``%APPLICATION%Configuration``:

    class %APPLICATION%Configuration extends sfApplicationConfiguration
	{
	...

    	public function configureDoctrine(Doctrine_Manager $manager)
    	{
      		// This is required for the Publishable behaviour
      		$manager->setAttribute(Doctrine_Core::ATTR_USE_DQL_CALLBACKS, true);  
    
      		// enable predql callback on the sfDoctrinePublishable plugin in this app
      		$manager->setAttribute('publishable_enable_predql', sfConfig::get('app_publishable_enable_predql', true));
    	}
    	...
	}

This will add the following SQL to the end of all queries that get MyModel in this application.

    WHERE ((((a.publish_until IS NULL AND a.published_at <= NOW()) OR NOW() BETWEEN a.published_at AND a.publish_until) AND (a.is_draft IS NULL OR a.is_draft != 1)))

*NOTE*: If there are other WHERE constraints, this will be added as an AND  
*NOTE*: `a` represents the root alias of MyModel


### 3. Add behaviour to model(s) in schema.yml

    MyModel:
      actAs: [Publishable]
      ...

This behaviour adds 3 fields to `MyModel` - `published_at`, `publish_until` and `is_draft`.

 * `published_at` is a required field - if this not set then the model won't appear when preDQL is enabled.  This sets the start date for the model 
to start appearing as a query result  
 * `publish_until` sets the end date for the model to stop appearing, if this is not set (e.g NULL) then the model appears indefinitely
 * `is_draft` allows models to have publish dates set, but not yet appear - for example, if a model requires approval it could be set as `is_draft`