#!/bin/bash  
step=10 #The number of seconds in the interval, cannot be greater than 60
  
for (( i = 0; i < 600000; i=(i+step) )); do  
    git pull  
    sleep $step  
done  
  
exit 0  
