MacOS:

[xdebug]
xdebug.remote_host = "docker.for.mac.host.internal"
xdebug.default_enable = 1
xdebug.remote_autostart = 1
xdebug.remote_connect_back = 0
xdebug.remote_enable = 1
xdebug.remote_handler = "dbgp"
xdebug.remote_port = 9000

Linux:

[xdebug]
xdebug.remote_host = "172.17.0.1"
xdebug.default_enable = 1
xdebug.remote_autostart = 1
xdebug.remote_connect_back = 0
xdebug.remote_enable = 1
xdebug.remote_handler = "dbgp"
xdebug.remote_port = 9000

Windows:

[xdebug]
xdebug.remote_host = "docker.for.win.host.internal"
xdebug.default_enable = 1
xdebug.remote_autostart = 1
xdebug.remote_connect_back = 0
xdebug.remote_enable = 1
xdebug.remote_handler = "dbgp"
xdebug.remote_port = 9000
This one is working for me, just replacing the "remote_host" with the value from:

docker network inspect bridge | grep Gateway
