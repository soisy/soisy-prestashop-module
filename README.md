# Soisy for PrestaShop
Increase conversions with Soisy installment payments: simple, fast, 100% online.
A "smaller" payment stimulates the purchase, promotes conversions, increases the average receipt. 


# Local development Environment

Docker is required in your host machine in order to run everything properly.  
You have a bunch of useful commands inside the `bin/` directory:

  - `bin/build`: Builds containers up from images (with `--no-cache` flag). Prestashop's installation is involved in this step.
  - `bin/up`: Gets prestashop's containers up and running
  - `bin/restart`: Restarts your containers
  - `bin/stop`: Stops your containers. Use this if you want to keep your containers data.
  - `bin/down`: Tears down your containers. !!! Containers' data loss involved!
  - `bin/rebuild_restart`: Tears containers down, builds them back and starts them again.


## First installation

Just run `$ bin/up` and you should be fine.

## Local Prestashop Front URL

You can then visit `http://localhost:8282` to see your local Prestashop up and running.


## Local Prestashop Admi URL
You can then visit `http://localhost:8282/admin-dev` and login with the following credentials:
```
Username/Email: demo@prestashop.com
Password:       prestashop_demo
```


## Soisy Plugin Development

Inside your `./src/` directory are all Soisy Module's files.
These are volumes mounted inside Prestashop containers at the following path `/var/www/html/modules/soisy`


## Soisy Plugin Configuration

The plugin configurations are available at this link: `http://localhost:8282/admin-dev/index.php?controller=AdminModules&configure=soisy`
Or you can login to your admin dashboard, on the left sidebar go under `Modules > Module Manager` and browse for `Soisy` and hit `Configure` and you are good to go.