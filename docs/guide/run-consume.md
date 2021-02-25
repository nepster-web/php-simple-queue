PHP Simple Queue run consume
=========================

Tips and tricks on consumer options.

## :book: Guide

* [Guide](./README.md)
* [Install](./install.md)
* [Usage basics](./usage.md)
* [Example](./example.md)
* **[Run consume](./run-consume.md)**
* [Cookbook](./cookbook.md)

## Run in background process

A consumer can be run in the background in several ways:

- [using php cli](#Using-php-cli)
- [run the application in a daemonized docker container](#Run-the-application-in-a-daemonized-docker-container)
- [using supervisor](#Using-supervisor)


### Using php cli
Configure your consume.php and run the command

```bash
exec php /path/to/folder/example/consume.php > /dev/null &
```
the result of a successful launch of the command will be the process code, for example:

```bash
[1] 97285
```

use this to get detailed information about the process.
```bash
ps -l 97285
```


### Run the application in a daemonized docker container

This command will allow your docker container to run in the background:

```bash
docker exec -t -d you-container-name sh -c "php ./path/to/consume.php"
```

#### Using supervisor

Сonfigure your supervisor config file `/etc/supervisor/conf.d/consume.conf`
```bash
[program:consume]
command=/usr/bin/php /path/to/folder/example/consume.php -DFOREGROUND
directory=/path/to/folder/example/
autostart=true
autorestart=true
startretries=5
user=root
numprocs=1
startsecs=0
process_name=%(program_name)s_%(process_num)02d
stderr_logfile=/path/to/folder/example/%(program_name)s_stderr.log
stderr_logfile_maxbytes=10MB
stdout_logfile=/path/to/folder/example/%(program_name)s_stdout.log
stdout_logfile_maxbytes=10MB
```

Let supervisor read our config file `/etc/supervisor/conf.d/consume.conf` to start our service/script.

```bash
$ sudo supervisorctl reread
consume: available
```

Let supervisor start our service/script `/path/to/folder/example/consume.php` based on the config we prepared above. 
This will automatically create log files `/path/to/folder/example/consume_stderr.log` and 
`/path/to/folder/example/consume_stdout.log`.

```bash
$ sudo supervisorctl update
consume: added process group
```

Lets check if the process is running.

```bash
$ ps aux | grep consume
root  17443  0.0  0.4 194836  9844 ?        S    19:41   0:00 /usr/bin/php /path/to/folder/example/consume.php
```