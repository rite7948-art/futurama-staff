@echo off
title Futurama Voice Tracker
echo Starting Voice Activity Tracker...
:loop
node voice_tracker.js
echo Bot crashed or stopped. Restarting in 10 seconds...
timeout /t 10
goto loop
