# cpanel-ddns

This project is intended to provide a cPanel server environment which mimics a DynDns compatible server. The credentials, hostname, and IP addresses are supplied to the script through a GET HTTPS request made by the client. The server script processes this request, extracts the credentials, hostname, and IP address and pushes the update to the cPanel DNS server through the cPanel API 2 interface. A response is returned to the client by outputting a string containing the response code and the request information, if applicable. The expected responses are described at https://www.dnsomatic.com/wiki/api and https://help.dyn.com/remote-access-api/return-codes/.

## Getting Started

These instructions assume you have basic working knowledge of cPanel, PHP, and DNS. There is also the assumption that your client (router, script, etc.) is capable of supplying the properly formed GET HTTPS request. It is important to note that modern web browsers tend to discourage (or prevent) the use of the HTTP Basic authentication method by supplying the credentials in the URL similar to the following:

```
https://username:password@www.example.com/
```

As such, a script is provided which uses cURL to properly form the request URL, and supply the HTTP Basic authentication header. The use of these scripts will be described later on.

### Prerequisites

There are a few things required to successfully implement this script. While some can be worked around, others should be considered essential to the safe use of secret credentials to ensure they are not leaked through an unsecured request.

The requirements of the cPanel server are the following:

* cPanel server configured with an SSL/TLS certificate issued by a trusted certificate authority.
* cPanel account on the above server.
* Domain with DNS A record to update with the supplied IP address, e.g. remote.example.com.

The requirements of the update server are the following:

* Server with mod_rewrite enabled...this can be the same as the cPanel server, but could also be a distinct non-cPanel web server with PHP, mod_rewrite capability.
* A domain or sub-domain to host the update script, e.g. ddns.example.com.
* Properly configured SSL/TLS certificate issued by a certificate authority (self-signed certificates are usable with modification to the code, but is not supported by the default configuration).

### Install and Configure

1. Login to the cPanel server.
2. Add an "A" record to the desired domain. e.g. if you want the updated hostname to be remote.example.com, then you would add the A record "remote" to the example.com DNS zone.
3. Set the TTL of this new record to something small like 30 or 60 seconds. When the IP is updated, this will be the maximum time the update will take to propagate through the internet.
4. Use a dummy value for the IP address. This will make it easy to identify that the address is updating when it is supposed to.
5a. If using the cPanel server as your update server, create a sub-domain to host the update script, e.g. ddns.example.com
5b. If using an external server, configure a domain or sub-domain to host the update script, e.g. example.net, ddns.example.net, etc.
6. On the above configured server, upload the scripts to the root of the web server. This includes the following files:
```
config.example.php
testclient.php
nic\.htaccess
nic\config.example.php
nic\update.php
```
7. Rename nic\config.example.php to nic\config.php. Update the appropriate values for each variable
```
//cPanel API user, password, server, and port
$cpUser = 'cpanelusername';
$cpPassword = 'cpanelpassword';
$cpServer = 'https://server.example.com';
$cpPort = '2083';

//Authorized ddns users, passwords, and hostnames
$authUsers=array('user1','user2');
$authPasswords=array('user1password','user2password');
$authHostnames=array('user1host.example.net','user2host.example.net');
```
8. $cpUser and $cpPassword contain the cPanel credentials. $cpServer is the server which you would normally use to login to your cPanel account. $cpPort is the port over which the API communication is done. Don't change this unless you know what you're doing.
9. A simple authentication method uses the array of $authHostnames to contain the hostnames which can be updated by an authenticated request. The $authUsers and $authPasswords values for a given index should correspond to the same index for the $authHostnames array.
10. Rename config.example.php to config.php. Update the appropriate values for each variable
```
//ddns client configuration details
$myip='1.2.3.4';
$hostname='user1host.example.net';
$login='user1';
$password='user1password';
$server='ddns.example.com';
```
11. $myip is the address, and $hostname is the hostname which will be updated when using testclient.php. This hostname must exist $authHostnames in nic\config.php
12. $login and $password must match the appropriate index in $authUsers and $authPasswords.
13. $server is the address of your update server.
14. This completes the installation and configuration of the script. 

## Test the Installation

Assuming that the config.php files are configured, you simply need to open the URL to testclient.php in a browser, like:
```
https://ddns.example.com/testclient.php
```

If everything is configured correctly, you should see a response similar to the following:
```
good 1.2.3.4 remote.example.com
```

The first word in the response is the response code indicating the general result of the request. A code of "good" indicates that the update completed successfully. You can login to the cPanel server to check that the record was updated with your specified value. The possible return codes in this script are:
```
good <= update was successful
nohost <= the hostname in the request is not found or configured correctly
badauth <= the username and/or password for the update is incorrect
dnserr <= there is an error in the cPanel DNS configuration such that the query was not successful
911 <= there is some other error in the update server configuration causing issues
```

The links at https://www.dnsomatic.com/wiki/api and https://help.dyn.com/remote-access-api/return-codes/ describe the codes in more detail. There are also some extra codes which have not been implemented yet, but perhaps will be in the future.

After the basic functionality is confirmed to work, update your ddns client with the appropriate hostname, user, password, and update server and force an update. Check the cPanel DNS configuration to ensure it was updated with the expected IP address. If the update was successful you could, and should, delete testclient.php and the corresponding config.php files since anyone accessing testclient.php would update it with the test IP address.

## Contributing

If you see something that should be changed, start a discussion or issue a pull request.

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details.
