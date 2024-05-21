#!/bin/bash

while getopts ":t:m:" opt; do
  case $opt in
    t)
      interval=$OPTARG
      ;;
    m)
      command=$OPTARG
      ;;
    \?)
      echo "Invalid option: -$OPTARG" >&2
      exit 1
      ;;
    :)
      echo "Option -$OPTARG requires an argument." >&2
      exit 1
      ;;
  esac
done

if [ -z "$interval" ] || [ -z "$command" ]; then
  echo "Usage: bash aks.sh -t <interval> -m <command>"
  exit 1
fi


Time=0
Times=120

while (( $Time<=$Times )); do
    $command
	sleep $interval
	echo "当前秒数为 : $Time"
	Time=`expr $Time + $interval`
done
