# Pause shop for Woocommerce

This is a Wordpress plugin that allows you to pause your Woocommerce shop for a period of time.

When paused, your customers will still be able to browse your shop, but they will not be able to add new products to their carts or place orders.

## Usage

You can pause your shop using the settings in the plugin's settings page or by using the REST endpoints provided by the plugin, which are documented in the same settings page and also in the following section.

There are two types of pause you can use:
1. Scheduled pause: This is a periodic pause that will start and end at specific times. You can set this pause to repeat on a daily, weekly or monthly basis, and also set the starting date.
2. On-demand pause: This is a pause that you can start and end at any time by clicking on the button in the settings page or by using the corresponding REST endpoints.

### REST endpoints

The plugin provides the following REST endpoints:
* Pause the shop on-demand.
```
POST /wp-json/pause-shop/v0/pause_shop
```
* Unpause the shop on-demand.
```
POST /wp-json/pause-shop/v0/unpause_shop
```
* Check if on-demand pause is on.
```
GET /wp-json/pause-shop/v0/is_on_demand_paused
```
* Check if scheduled pause is on.
```
GET /wp-json/pause-shop/v0/is_scheduled_paused
```
* Set the timezone.
```
POST /wp-json/pause-shop/v0/set_timezone -F "timezone=Europe/London"
```
* Get the timezone.
```
GET /wp-json/pause-shop/v0/get_timezone
```
* Set the scheduled pause begin time.
```
POST /wp-json/pause-shop/v0/set_begin_time -F "begin_time=01:00"
```
* Get the scheduled pause begin time.
```
GET /wp-json/pause-shop/v0/get_begin_time
```
* Set the scheduled pause end time.
```
POST /wp-json/pause-shop/v0/set_end_time -F "end_time=01:30"
```
* Get the scheduled pause end time.
```
GET /wp-json/pause-shop/v0/get_end_time
```
* Set the scheduled pause periodicity.
```
POST /wp-json/pause-shop/v0/set_periodicity -F "periodicity=monthly"
```
* Get the scheduled pause periodicity.
```
GET /wp-json/pause-shop/v0/get_periodicity
```
* Set the begin date for the scheduled pause.
```
POST /wp-json/pause-shop/v0/set_begin_date -F "begin_date=2020-01-01"
```
* Get the begin date for the scheduled pause.
```
GET /wp-json/pause-shop/v0/get_begin_date
```
* Enable the scheduled pause.
```
POST /wp-json/pause-shop/v0/enable_scheduled_pause
```
* Disable the scheduled pause.
```
POST /wp-json/pause-shop/v0/disable_scheduled_pause
```
* Check if the scheduled pause is enabled.
```
GET /wp-json/pause-shop/v0/is_scheduled_pause_enabled
```

## License

This plugin is licensed under the MIT License. See the LICENSE file for more details.

## Donations

If you like this plugin and want to support its development, you can buy me a coffee at [Ko-fi](https://ko-fi.com/y3ro752694).

<a href="https://ko-fi.com/y3ro752694" target="_blank">
    <img height="36" style="border:0px;height:36px;" 
    src="https://cdn.ko-fi.com/cdn/kofi1.png?v=2" 
    alt="Buy me a coffe if you like this repo" />
</a>