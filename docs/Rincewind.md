# Rincewind

This document covers the actions performed when a user clicks "Create VPC" in MyUKFast.

- VPC Create is hit...
  - VPC made connected to a region

- VpcCreated event caught by the DhcpCreate listener
  - DHCP made connected to the VPC

- DhcpCreated event caught by the DhcpDeploy listener
  - Dhcp is deployed to NSX in each AZ of the VPC's regions

- deployDefaults is hit...
  - Router made for the first AZ of the VPC's region
  - Placeholder Network made (Not deployed) and linked to the Router and same AZ

- RouterCreated event caught by the RouterDeploy listener
  - Router is deployed to NSX
  - FirewallRule is created, linked to the Router
  - All networks of the Router have their NetworkCreated event fired manually

- NetworkCreated event caught by the NetworkDeploy listener
  - Network is deployed to NSX in the AZ it is directly linked too

- FirewallRuleCreated event caught by the FirewallRuleDeploy listener
  - FirewallRule is deployed to NSX....

