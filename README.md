## Synopsis

Send OAuth 1.0 requests.

## Code Example
```
oauth_frameweld::init()->
setRequestOptions(
	array(
	 	'public_key' => $keys['api_key'],
	 	'private_key' => $keys['api_key_private'],
	 	'api_url' => $api_url_resource,
	 	'method' => 'GET'
	)		
)->
sendRequest();
			
if (oauth_frameweld::isError()) {
	echo oauth_frameweld::getErrorMessage();
	exit;
}
 *
print_r('<pre>');
print_r(oauth_frameweld::getResponse());
print_r('<pre>');
print_r(oauth_frameweld::getHeaders());	
```
## License

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.
This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
