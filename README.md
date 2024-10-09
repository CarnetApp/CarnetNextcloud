# Carnet
Place this app in **nextcloud/apps/**

What is Carnet ?

<a href="https://framagit.org/PhieF/CarnetDocumentation">Documentation and description of Carnet are available here</a>


## Help

You can help with translations on the dedicated platform

[weblate](https://weblate.lostpod.me)


## Development

Carnet For Nextcloud is divided into two parts:

- The server part (this repo)
- And the client part [here](../CarnetWebClient)


### Prepare your environment

To start developping Carnet, you will need a working Nextcloud install

Then, from your favorite dev folder:

Create a carnet folder where all the dev for server and clients will happen:

```
mkdir carnet
cd carnet
```

Follow instructions on building [CarnetWebClient](https://github.com/CarnetApp/CarnetWebClient)

Link it to templates inside the nextcloud app

```
git clone https://github.com/CarnetApp/CarnetNextcloud
current=`pwd`
cd CarnetNextcloud
ln -s "$current"/CarnetWebClient/dist templates/CarnetWebClient
```
This should create a link called CarnetWebClient to the dist folder of CarnetWebClient, inside templates

Then create a link to your nextcloud installation (replace nextcloud-install-folder with appropriate path)

```
ln -s "$current"/CarnetNextcloud nextcloud-install-folder/apps/carnet
```

Then activate is within nextcloud interface !