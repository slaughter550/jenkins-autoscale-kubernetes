Jenkins Worker Autoscaler for Kubernetes Based Executors
=====================

This script provides the ability to automate the number of Kubernetes nodes available to Jenkins at any given time. The goal of this script is to help with cost savings by only having the number of executors needed at any given time without manual management. Improvements on the scaling algorithm are welcome.

Requirements
----
* Jenkins
* Kubernetes
* Remote Service
  * php
  * gcloud
  * composer

Setup
----
