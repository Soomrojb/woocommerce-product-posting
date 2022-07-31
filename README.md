# WooCommerce Bulk Product Posting

Posting simple/variable products at WordPress has never been easy and fun before. Folks at WooCommerce have tried their level best to make a perfect solution using the "REST API" and it does work well, however things gets complicated when you switch from 'simple' to 'variable' products. My script makes whole process easy by handling things itself, you are only required to send an array through a custom route and this script handles everything for you.

## Usage
+ place the php script ```wpfunctions.php``` in active theme folder
+ add following line at the end of functions.php:
+ ```include( get_template_directory() . '/wpfunctions.php' );```
+ generate product_array and send to ```https://www.domain.com/index.php/wp-json/extwoo/add-variable-product```

## Postman Sample

```
POST http://wordpress.local/index.php/wp-json/extwoo/add-variable-product
content-type: application/json

{
    "author": "1",
    "product_title": "Product title",
    "product_slug": "product_slug",
    "long_description": "This is long_description",
    "short_description": "This is short_description",
    "canonical_url": "http://demo.canonical.com",
    "meta_title": "This is meta title",
    "meta_description": "This is meta_description",
    "focus_keyword": "First focus keyword",
    "sku": "simple_product_sku",
    "regular_price": 200,
    "price": 200,
    "sale_price": 180,
    "purchase_note": "This is purchase_note",
    "sold_individually": "no",
    "yith_brand": "3M",
    "images": [
        "https://multimedia.3m.com/mws/media/1599039P/3m-speedglas-heavy-duty-welding-helmet-g5-01-without-welding-filter.jpg",
        "https://multimedia.3m.com/mws/media/1920696P/3m-speedglas-heavy-duty-welding-helmet-g5-01-46-0099-35.jpg"
    ],
    "categories": ["Clamps", "Sleeves and Ferrules", "Teaser"],
    "specifications": {
        "Battery Life": ["6 Hour"], "Battery Type": ["Economy Battery"],
"Brand": ["Versaflo™"], "Specifications Met": ["NIOSH Approved"]
    },
    "variations": [
        {
            "attributes": {
                "Size": ["6 feet"],
                "Color": ["Red"]
            },
            "sku": "variation_1_sku",
            "regular_price": 200,
            "sale_price": 180,
            "stock_qty": 10
        },
        {
            "attributes": {
                "Size": ["9 feet"],
                "Color": ["Green"]
            },
            "sku": "variation_2_sku",
            "regular_price": 400,
            "sale_price": 380,
            "stock_qty": 15
        }
    ],
    "attachments": "<p>Document attachment tab - Woomart theme specific</p><p>Ignore if other theme</p>"
}
```

## License
Feel free to use in your projects!

## Contributing
Any contributions you make are greatly appreciated.

## Contact

Janib Soomro | soomrojb@gmail.com | +92 (333) 3640-375