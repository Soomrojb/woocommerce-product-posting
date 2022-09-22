# WooCommerce Bulk Product Posting

Posting simple/variable products at WordPress has never been easy and fun before. Folks at WooCommerce have tried their level best to make a perfect solution using the "REST API" and it does work well, however things gets complicated when you switch from 'simple' to 'variable' products. My script makes whole process easy by handling things itself, you are only required to send an array through a custom route and this script handles everything for you.

## Usage
+ place the php script ```wpfunctions.php``` in active theme folder
+ add following line at the end of functions.php:
+ ```include( get_template_directory() . '/wpfunctions.php' );```
+ for child theme:  ```include( '/wpfunctions.php' );```
+ generate product_array and send to ```https://www.domain.com/index.php/wp-json/extwoo/add-variable-product```

## Postman Sample (JSON)

```
POST http://wordpress.local/index.php/wp-json/extwoo/add-variable-product
content-type: application/json

{
    "author": "1",
    "product_title": "Product title",
    "product_slug": "product_slug",
    "post_status": "publish",
    "ping_status": "open",
    "long_description": "This is long_description",
    "short_description": "This is short_description",
    "canonical_url": "http://demo.canonical.com",
    "source_url": "http://product.fetching.source.com",
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
    "stock_status": "instock",
    "tags": ["tag #1","tag2 #2"],
    "images": [
        {
            "href": "https://multimedia.3m.com/mws/media/1599039P/3m-speedglas-heavy-duty-welding-helmet-g5-01-without-welding-filter.jpg",
            "alt": "image alt",
            "title": "image title",
            "caption": "image caption",
            "description": "image description"
        },
        {
            "href": "https://multimedia.3m.com/mws/media/1920696P/3m-speedglas-heavy-duty-welding-helmet-g5-01-46-0099-35.jpg",
            "alt": "image alt",
            "title": "image title",
            "caption": "image caption",
            "description": "image description"
        }
    ],
    "categories": ["Clamps", "Sleeves and Ferrules", "Teaser"],
    "post_in_all_categories": true,
    "specifications": {
        "Battery Life": ["6 Hour"],
        "Battery Type": ["Economy Battery"],
        "Brand": ["Versaflo™"],
        "Specifications Met": ["NIOSH Approved"]
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
            "sale_start": "2022-08-20",
            "sale_end": "2022-08-23",
            "stock_qty": 10,
            "back_order": "no",
            "manage_stock": false,
            "weight": "2kg",
            "length": 33,
            "height": 5,
            "width": 6,
            "short_description": "variation_1_sku description"
        },
        {
            "attributes": {
                "Size": ["9 feet"],
                "Color": ["Green"]
            },
            "sku": "variation_2_sku",
            "regular_price": 400,
            "sale_price": 380,
            "sale_start": "",
            "sale_end": "2022-08-25",
            "back_order": "no",
            "stock_qty": 15,
            "manage_stock": false,
            "weight": "1kg",
            "length": 22,
            "height": 2,
            "width": 5,
            "short_description": "variation_2_sku description"
        }
    ],
    "shipping": {
        "weight": "5kg",
        "length": 44,
        "height": 12,
        "width": 15
    },
    "attachments": "<p>Document attachment tab - Woomart theme specific</p><p>Ignore if other theme</p>"
}
```

### What is still missing?
+ individual variation price, sku and supporting details

## License
Feel free to use in your projects!

## Contributing
Any contributions you make are greatly appreciated.

## Contact

Janib Soomro | soomrojb@gmail.com | +92 (333) 3640-375