# crawr

Web UI to download manhua and manhwa.

## Supported sites

- Ac
- Bomtoon
- Dongmanmanhua
- Kuaikanmanhua
- Manman
- MrBlue
- Toonkor

## Usage

Clone the repo and use `composer install` to download its dependencies or use
the Docker container:

```
$ git clone https://github.com/chopshopnacho/crawr.git
$ docker build -t crawr crawr
$ docker run --publish 8000:80 crawr
```
