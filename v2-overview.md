```mermaid
classDiagram
    class region {
        char[12] id
        char[255] name
    }

    class vpc {
        char[12] id
        char[12] region_id
        varchar[255] name
    }
    vpc --> region

    class site {
        char[12] id
        char[12] region_id
    }
    site --> region

    class availability_zone {
        char[12] id 
        char[12] site_id
        varchar[255] code
        varchar[255] name
    }
    availability_zone --> site

    class gateway {
        char[12] id
        char[12] availability_zone_id
    }
    gateway --> availability_zone

    class network {
        char[12] id
        char[12] router_id
        char[12] availability_zone_id
    }
    network --> router
    network --> availability_zone

    class router {
        char[12] id
        char[12] vpc_id
        char[12] gateway_id
        char[12] availability_zone_id
    }
    router --> vpc
    router --> gateway
    router --> availability_zone

    class vpn {
        char[12] id
        char[12] router_id
        char[12] availability_zone_id
    }
    vpn --> router
    vpn --> availability_zone

    class instance {
        char[12] id
        char[12] network_id
    }
    instance --> network
```

# Notes

- The DHCP server needs to be added in somewhere, but we need to know what is required. What needs to be federated? What needs to be configered by the customer? etc..
- Need VPN connections/properties confirming with JL and Joe
- Instance storage is not displayed on this UML but may in future
