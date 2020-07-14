```mermaid
classDiagram
    class region {
        uuid id
        char[255] name
    }

    class vpc {
        uuid id
        uuid region_id
        varchar[255] name
    }
    vpc --> region

    class site {
        uuid id
        uuid region_id
    }
    site --> region

    class availability_zone {
        uuid id 
        uuid site_id
        varchar[255] code
        varchar[255] name
    }
    availability_zone --> site

    class gateway {
        uuid id
        uuid availability_zone_id
    }
    gateway --> availability_zone

    class network {
        uuid id
        uuid router_id
        uuid availability_zone_id
    }
    network --> router
    network --> availability_zone

    class router {
        uuid id
        uuid vpc_id
    }
    router --> vpc
    router --> "many" gateway : router_gateway
    router --> "many" availability_zone : router_availability_zone

    class vpn {
        uuid id
        uuid router_id
        uuid availability_zone_id
    }
    vpn --> router
    vpn --> availability_zone

    class instance {
        uuid id
        uuid network_id
    }
    instance --> network
```

# Notes

- The DHCP server needs to be added in somewhere, but we need to know what is required. What needs to be federated? What needs to be configered by the customer? etc..
- Need VPN connections/properties confirming with JL and Joe
- Instance storage is not displayed on this UML but may in future
