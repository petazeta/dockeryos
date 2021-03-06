
![Your Online Shop](images/logotype.svg "Your Online Shop")

----------------------------------------------------------------------

# Your Online Shop System V 3.6.3

## Live Demo

Video: https://youtu.be/PD_olszbGWA

Shop with sample products -> [Your Online Shop Demo Shop](https://youronlineshop.net/sample/)

Admin -> (usr/password) webadmin/webadmin, productsadmin/productsadmin


## About

This is a dynamic web app (Single Page Application) for e-commerce. Backend is written in both Php and Nodejs so you can choose your preferred one. It uses Mysql database system.

https://youronlineshop.net


## Installation

If you prefer we offer FREE YOUR ONLINE SHOP installation and hosting service: more info by email.

1. Upload the compressed file to your web server.
2. Unpack it to the desired folder (files could be inside a folder or not).
3. Create a new mysql database.
4. Edit database settings at: server/nodejs/includes/dbcfg.json (Node Js) or server/php/includes/config.php (Php).
5. Nodejs: Open your terminal at main folder and type: npm install and then npm test. Once the server is running then you can open your web browser (port 8080) and continue with the installation process.
Php: Open your web browser (yos folder) and continue with the installation process.

## Support

Please write: info@youronlineshop.net


## Getting started

Once Your Online Shop is installed you can open your browser at the corresponding url address. Lets log-in with some of the admin users to make changes: user **webadmin** for web content and **productsadmin** for catalog content. Other operations can be done by user **systemadmin**, and to check customer orders log-in with **ordersadmin**.

Once editing some content press Intro or click outside of the editable area to save changes.

To edit some features from the checkout process you should log in with systemadmin user and make an order as if you were a customer. Once you get to the checkout step you should be able to edit checkout options.

Some other configuration can be done by editing file javascript/config.js.


## Troubleshooting

If the database connection is not working you may need to check settings at server/php/includes/config.php or server/nodejs/includes/dbcfg.json. Also you may need to import the database content directly from the file server/utils/database.sql.

If the products images can not be uploaded you may need to allow write permisions at folders catalog/images/big and catalog/images/small.

If it doesn't allow admin to make changes you can try to copy server/utils/htaccess file to the folder server/php and rename it to ".htaccess".


## Themes


Y.O.S. system has a themes feature. You can select themes and styles at the **config.js** file. Themes files are inside folder themes/[theme path]/views.
