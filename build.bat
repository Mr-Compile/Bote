@echo off
set "NODE_DIR=C:\wamp64\bin\nodejs\v20.20.2"
set "PATH=%NODE_DIR%;%PATH%"
call npm run build
