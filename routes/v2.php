1
2
3
4
5
6
7
8
9
10
11
12
13
14
15
16
17
18
19
20
21
22
23
24
25
26
27
28
29
30
31
32
33
34
35
36
37
38
39
40
41
42
43
44
45
46
47
48
49
50
51
52
53
54
55
56
57
58
59
60
61
62
63
64
65
66
67
68
69
70
71
72
73
74
75
76
77
78
79
80
81
82
83
84
85
86
87
88
89
90
91
92
93
94
95
96
97
98
99
100
101
102
103
104
105
106
107
108
109
110
111
112
113
114
115
116
117
118
119
120
121
122
123
124
125
126
127
128
129
130
131
132
133
134
135
136
137
138
139
140
141
142
143
144
145
146
147
148
149
150
151
152
153
154
155
156
157
158
159
160
161
162
163
164
165
166
167
168
169
170
171
172
173
174
175
176
177
178
179
180
181
182
183
184
185
186
187
188
189
190
191
192
193
194
195
196
197
198
199
200
201
202
203
204
205
206
207
208
209
210
211
212
213
214
215
216
217
218
219
220
221
222
223
224
225
226
227
228
229
230
231
232
233
234
235
236
237
238
239
240
241
242
243
244
245
246
247
248
249
250
251
252
253
254
255
256
257
258
259
260
261
262
263
264
265
266
267
268
269
270
271
272
273
274
275
276
277
278
279
280
281
282
283
284
285
286
287
288
289
290
291
292
293
294
295
296
297
298
299
300
301
302
303
304
305
306
307
308
309
310
311
312
313
314
315
316
317
318
319
320
321
322
323
324
325
326
327
328
329
330
331
332
333
334
335
336
337
338
339
340
341
342
343
344
345
346
347
348
349
350
351
352
353
354
355
356
357
358
359
360
361
362
363
364
365
366
367
368
369
370
371
372
373
374
375
376
377
378
379
380
381
382
383
384
385
386
387
388
389
390
391
392
393
394
395
396
397
398
399
400
401
402
403
404
405
406
407
408
409
410
411
412
413
414
415
416
417
418
419
420
421
422
423
424
425
426
427
428
429
430
431
432
433
434
435
<?php
/**
 * v2 Routes
 */
use Laravel\Lumen\Routing\Router;
$middleware = [
    'auth',
    'paginator-limit:' . env('PAGINATION_LIMIT')
];
$baseRouteParameters = [
    'prefix' => 'v2',
    'namespace' => 'V2',
    'middleware' => $middleware
];
/** @var Router $router */
$router->group($baseRouteParameters, function () use ($router) {
    /** Availability Zones */
    $router->get('availability-zones', 'AvailabilityZoneController@index');
    $router->get('availability-zones/{zoneId}', 'AvailabilityZoneController@show');
    $router->get('availability-zones/{zoneId}/prices', 'AvailabilityZoneController@prices');
    $router->get('availability-zones/{zoneId}/router-throughputs', 'AvailabilityZoneController@routerThroughputs');
    $router->get('availability-zones/{zoneId}/host-specs', 'AvailabilityZoneController@hostSpecs');
    $router->get('availability-zones/{zoneId}/images', 'AvailabilityZoneController@images');
    $router->group(['middleware' => 'is-admin'], function () use ($router) {
        $router->post('availability-zones', 'AvailabilityZoneController@create');
        $router->patch('availability-zones/{zoneId}', 'AvailabilityZoneController@update');
        $router->delete('availability-zones/{zoneId}', 'AvailabilityZoneController@destroy');
        $router->get('availability-zones/{zoneId}/routers', 'AvailabilityZoneController@routers');
        $router->get('availability-zones/{zoneId}/dhcps', 'AvailabilityZoneController@dhcps');
        $router->get('availability-zones/{zoneId}/credentials', 'AvailabilityZoneController@credentials');
        $router->get('availability-zones/{zoneId}/instances', 'AvailabilityZoneController@instances');
        $router->get('availability-zones/{zoneId}/lbcs', 'AvailabilityZoneController@lbcs');
        $router->get('availability-zones/{zoneId}/capacities', 'AvailabilityZoneController@capacities');
    });
    /** Availability Zone Capacities */
    $router->group(['middleware' => 'is-admin'], function () use ($router) {
        $router->get('availability-zone-capacities', 'AvailabilityZoneCapacitiesController@index');
        $router->get('availability-zone-capacities/{capacityId}', 'AvailabilityZoneCapacitiesController@show');
        $router->post('availability-zone-capacities', 'AvailabilityZoneCapacitiesController@create');
        $router->patch('availability-zone-capacities/{capacityId}', 'AvailabilityZoneCapacitiesController@update');
        $router->delete('availability-zone-capacities/{capacityId}', 'AvailabilityZoneCapacitiesController@destroy');
    });
    /** Virtual Private Clouds */
    $router->group([], function () use ($router) {
        $router->group(['middleware' => 'has-reseller-id'], function () use ($router) {
            $router->group(['middleware' => 'customer-max-vpc'], function () use ($router) {
                $router->post('vpcs', 'VpcController@create');
            });
            $router->post('vpcs/{vpcId}/deploy-defaults', 'VpcController@deployDefaults');
        });
        $router->patch('vpcs/{vpcId}', 'VpcController@update');
        $router->get('vpcs', 'VpcController@index');
        $router->get('vpcs/{vpcId}', 'VpcController@show');
        $router->delete('vpcs/{vpcId}', 'VpcController@destroy');
        $router->get('vpcs/{vpcId}/volumes', 'VpcController@volumes');
        $router->get('vpcs/{vpcId}/instances', 'VpcController@instances');
        $router->get('vpcs/{vpcId}/tasks', 'VpcController@tasks');
        $router->group(['middleware' => 'is-admin'], function () use ($router) {
            $router->get('vpcs/{vpcId}/lbcs', 'VpcController@lbcs');
        });
    });
    /** Dhcps */
    $router->group([], function () use ($router) {
        $router->get('dhcps', 'DhcpController@index');
        $router->get('dhcps/{dhcpId}', 'DhcpController@show');
        $router->get('dhcps/{dhcpId}/tasks', 'DhcpController@tasks');
        $router->group(['middleware' => 'is-admin'], function () use ($router) {
            $router->post('dhcps', 'DhcpController@create');
            $router->patch('dhcps/{dhcpId}', 'DhcpController@update');
            $router->delete('dhcps/{dhcpId}', 'DhcpController@destroy');
        });
    });
    /** Networks */
    $router->group([], function () use ($router) {
        $router->get('networks', 'NetworkController@index');
        $router->get('networks/{networkId}', 'NetworkController@show');
        $router->get('networks/{networkId}/nics', 'NetworkController@nics');
        $router->get('networks/{networkId}/tasks', 'NetworkController@tasks');
        $router->post('networks', 'NetworkController@create');
        $router->patch('networks/{networkId}', 'NetworkController@update');
        $router->delete('networks/{networkId}', 'NetworkController@destroy');
    });
    /** Network Policy */
    $router->group([], function () use ($router) {
        $router->get('network-policies', 'NetworkPolicyController@index');
        $router->get('network-policies/{networkPolicyId}', 'NetworkPolicyController@show');
        $router->get('network-policies/{networkPolicyId}/network-rules', 'NetworkPolicyController@networkRules');
        $router->get('network-policies/{networkPolicyId}/tasks', 'NetworkPolicyController@tasks');
        $router->post('network-policies', 'NetworkPolicyController@store');
        $router->patch('network-policies/{networkPolicyId}', 'NetworkPolicyController@update');
        $router->delete('network-policies/{networkPolicyId}', 'NetworkPolicyController@destroy');
    });
    /** Network Rules */
    $router->group([], function () use ($router) {
        $router->get('network-rules', 'NetworkRuleController@index');
        $router->get('network-rules/{networkRuleId}', 'NetworkRuleController@show');
        $router->post('network-rules', 'NetworkRuleController@store');
        $router->group(['middleware' => 'network-rule-can-edit'], function () use ($router) {
            $router->patch('network-rules/{networkRuleId}', 'NetworkRuleController@update');
        });
        $router->group(['middleware' => 'network-rule-can-delete'], function () use ($router) {
            $router->delete('network-rules/{networkRuleId}', 'NetworkRuleController@destroy');
        });
    });
    /** Network Rule Ports */
    $router->group([], function () use ($router) {
        $router->get('network-rule-ports', 'NetworkRulePortController@index');
        $router->get('network-rule-ports/{networkRulePortId}', 'NetworkRulePortController@show');
        $router->post('network-rule-ports', 'NetworkRulePortController@store');
        $router->group(['middleware' => 'network-rule-port-can-edit'], function () use ($router) {
            $router->patch('network-rule-ports/{networkRulePortId}', 'NetworkRulePortController@update');
        });
        $router->group(['middleware' => 'network-rule-port-can-delete'], function () use ($router) {
            $router->delete('network-rule-ports/{networkRulePortId}', 'NetworkRulePortController@destroy');
        });
    });

    /** Vpn Services */
    $router->group([], function () use ($router) {
        $router->get('vpn-services', 'VpnServiceController@index');
        $router->get('vpn-services/{vpnServiceId}', 'VpnServiceController@show');
        $router->post('vpn-services', 'VpnServiceController@create');
        $router->patch('vpn-services/{vpnServiceId}', 'VpnServiceController@update');
        $router->delete('vpn-services/{vpnServiceId}', 'VpnServiceController@destroy');
    });

    /** VPN Endpoints */
    $router->group([], function () use ($router) {
        $router->get('vpn-endpoints', 'VpnEndpointController@index');
        $router->get('vpn-endpoints/{vpnEndpointId}', 'VpnEndpointController@show');
        $router->post('vpn-endpoints', 'VpnEndpointController@store');
        $router->patch('vpn-endpoints/{vpnEndpointId}', 'VpnEndpointController@update');
        $router->delete('vpn-endpoints/{vpnEndpointId}', 'VpnEndpointController@destroy');
    });

    /** Vpn Sessions */
    $router->group([], function () use ($router) {
        $router->get('vpn-sessions', 'VpnSessionController@index');
        $router->get('vpn-sessions/{vpnSessionId}', 'VpnSessionController@show');
        $router->post('vpn-sessions', 'VpnSessionController@create');
        $router->patch('vpn-sessions/{vpnSessionId}', 'VpnSessionController@update');
        $router->delete('vpn-sessions/{vpnSessionId}', 'VpnSessionController@destroy');
    });

    /** Routers */
    $router->group([], function () use ($router) {
        $router->get('routers', 'RouterController@index');
        $router->get('routers/{routerId}', 'RouterController@show');
        $router->get('routers/{routerId}/networks', 'RouterController@networks');
        $router->get('routers/{routerId}/vpns', 'RouterController@vpns');
        $router->get('routers/{routerId}/firewall-policies', 'RouterController@firewallPolicies');
        $router->get('routers/{routerId}/tasks', 'RouterController@tasks');
        $router->post('routers', 'RouterController@create');
        $router->patch('routers/{routerId}', 'RouterController@update');
        $router->delete('routers/{routerId}', 'RouterController@destroy');
        $router->post('routers/{routerId}/configure-default-policies', 'RouterController@configureDefaultPolicies');
    });

    /** Instances */
    $router->group([], function () use ($router) {
        $router->group(['middleware' => 'customer-max-instance'], function () use ($router) {
            $router->post('instances', 'InstanceController@store');
        });
        $router->get('instances', 'InstanceController@index');
        $router->get('instances/{instanceId}', 'InstanceController@show');
        $router->get('instances/{instanceId}/credentials', 'InstanceController@credentials');
        $router->get('instances/{instanceId}/volumes', 'InstanceController@volumes');
        $router->get('instances/{instanceId}/nics', 'InstanceController@nics');
        $router->get('instances/{instanceId}/tasks', 'InstanceController@tasks');
        $router->put('instances/{instanceId}/lock', 'InstanceController@lock');
        $router->put('instances/{instanceId}/unlock', 'InstanceController@unlock');
        $router->post('instances/{instanceId}/console-session', 'InstanceController@consoleSession');
        $router->post('instances/{instanceId}/create-image', 'InstanceController@createImage');
        $router->post('instances/{instanceId}/migrate', 'InstanceController@migrate');

        $router->group(['middleware' => 'is-locked'], function () use ($router) {
            $router->patch('instances/{instanceId}', 'InstanceController@update');
            $router->delete('instances/{instanceId}', 'InstanceController@destroy');
            $router->put('instances/{instanceId}/power-on', 'InstanceController@powerOn');
            $router->put('instances/{instanceId}/power-off', 'InstanceController@powerOff');
            $router->put('instances/{instanceId}/power-reset', 'InstanceController@powerReset');
            $router->put('instances/{instanceId}/power-restart', 'InstanceController@guestRestart');
            $router->put('instances/{instanceId}/power-shutdown', 'InstanceController@guestShutdown');
            $router->group(['middleware' => 'can-attach-instance-volume'], function () use ($router) {
                $router->post('instances/{instanceId}/volume-attach', 'InstanceController@volumeAttach');
            });
            $router->post('instances/{instanceId}/volume-detach', 'InstanceController@volumeDetach');
        });
    });

    /** Floating Ips */
    $router->group([], function () use ($router) {
        $router->get('floating-ips', 'FloatingIpController@index');
        $router->get('floating-ips/{fipId}', 'FloatingIpController@show');
        $router->get('floating-ips/{fipId}/tasks', 'FloatingIpController@tasks');
        $router->post('floating-ips', 'FloatingIpController@store');
        $router->post('floating-ips/{fipId}/assign', 'FloatingIpController@assign');
        $router->post('floating-ips/{fipId}/unassign', 'FloatingIpController@unassign');
        $router->patch('floating-ips/{fipId}', 'FloatingIpController@update');
        $router->delete('floating-ips/{fipId}', 'FloatingIpController@destroy');
    });

    /** Firewall Policy */
    $router->group([], function () use ($router) {
        $router->get('firewall-policies', 'FirewallPolicyController@index');
        $router->get('firewall-policies/{firewallPolicyId}', 'FirewallPolicyController@show');
        $router->get('firewall-policies/{firewallPolicyId}/firewall-rules', 'FirewallPolicyController@firewallRules');
        $router->get('firewall-policies/{firewallPolicyId}/tasks', 'FirewallPolicyController@tasks');
        $router->post('firewall-policies', 'FirewallPolicyController@store');
        $router->patch('firewall-policies/{firewallPolicyId}', 'FirewallPolicyController@update');
        $router->delete('firewall-policies/{firewallPolicyId}', 'FirewallPolicyController@destroy');
    });

    /** Firewall Rules */
    $router->group([], function () use ($router) {
        $router->get('firewall-rules', 'FirewallRuleController@index');
        $router->get('firewall-rules/{firewallRuleId}', 'FirewallRuleController@show');
        $router->get('firewall-rules/{firewallRuleId}/ports', 'FirewallRuleController@ports');
        $router->post('firewall-rules', 'FirewallRuleController@store');
        $router->patch('firewall-rules/{firewallRuleId}', 'FirewallRuleController@update');
        $router->delete('firewall-rules/{firewallRuleId}', 'FirewallRuleController@destroy');
    });

    /** Firewall Rule Ports */
    $router->group([], function () use ($router) {
        $router->get('firewall-rule-ports', 'FirewallRulePortController@index');
        $router->get('firewall-rule-ports/{firewallRulePortId}', 'FirewallRulePortController@show');
        $router->post('firewall-rule-ports', 'FirewallRulePortController@store');
        $router->patch('firewall-rule-ports/{firewallRulePortId}', 'FirewallRulePortController@update');
        $router->delete('firewall-rule-ports/{firewallRulePortId}', 'FirewallRulePortController@destroy');
    });

    /** Regions */
    $router->group([], function () use ($router) {
        $router->get('regions', 'RegionController@index');
        $router->get('regions/{regionId}', 'RegionController@show');
        $router->get('regions/{regionId}/availability-zones', 'RegionController@availabilityZones');
        $router->get('regions/{regionId}/vpcs', 'RegionController@vpcs');
        $router->get('regions/{regionId}/prices', 'RegionController@prices');

        $router->group(['middleware' => 'is-admin'], function () use ($router) {
            $router->post('regions', 'RegionController@create');
            $router->patch('regions/{regionId}', 'RegionController@update');
            $router->delete('regions/{regionId}', 'RegionController@destroy');
        });
    });

    /** Load balancer clusters */
    $router->group([], function () use ($router) {
        $router->get('lbcs', 'LoadBalancerClusterController@index');
        $router->get('lbcs/{lbcId}', 'LoadBalancerClusterController@show');
        $router->post('lbcs', 'LoadBalancerClusterController@store');
        $router->patch('lbcs/{lbcId}', 'LoadBalancerClusterController@update');
        $router->delete('lbcs/{lbcId}', 'LoadBalancerClusterController@destroy');
    });

    /** Volumes */
    $router->group([], function () use ($router) {
        $router->group(['middleware' => 'can-detach'], function () use ($router) {
            $router->post('volumes/{volumeId}/detach', 'VolumeController@detach');
        });
        $router->get('volumes', 'VolumeController@index');
        $router->get('volumes/{volumeId}', 'VolumeController@show');
        $router->get('volumes/{volumeId}/instances', 'VolumeController@instances');
        $router->get('volumes/{volumeId}/tasks', 'VolumeController@tasks');
        $router->post('volumes', 'VolumeController@store');
        $router->patch('volumes/{volumeId}', 'VolumeController@update');
        $router->delete('volumes/{volumeId}', 'VolumeController@destroy');
        $router->post('volumes/{volumeId}/attach', 'VolumeController@attach');
    });

    /** Nics */
    $router->group([], function () use ($router) {
        $router->get('nics', 'NicController@index');
        $router->get('nics/{nicId}', 'NicController@show');
        $router->get('nics/{nicId}/tasks', 'NicController@tasks');
        $router->group(['middleware' => 'is-admin'], function () use ($router) {
            //$router->post('nics', 'NicController@create');
            $router->patch('nics/{nicId}', 'NicController@update');
            $router->delete('nics/{nicId}', 'NicController@destroy');
        });
    });

    /** Credentials */
    $router->group(['middleware' => 'is-admin'], function () use ($router) {
        $router->get('credentials', 'CredentialsController@index');
        $router->get('credentials/{credentialsId}', 'CredentialsController@show');
        $router->post('credentials', 'CredentialsController@store');
        $router->patch('credentials/{credentialsId}', 'CredentialsController@update');
        $router->delete('credentials/{credentialsId}', 'CredentialsController@destroy');
    });

    /** Support */
    $router->group([], function () use ($router) {
        $router->get('support', 'VpcSupportController@index');
        $router->get('support/{vpcSupportId}', 'VpcSupportController@show');
        $router->group(['middleware' => 'can-enable-support'], function () use ($router) {
            $router->post('support', 'VpcSupportController@create');
            $router->patch('support/{vpcSupportId}', 'VpcSupportController@update');
        });
        $router->delete('support/{vpcSupportId}', 'VpcSupportController@destroy');
    });

    /** Discount Plans */
    $router->group([], function () use ($router) {
        $router->get('discount-plans', 'DiscountPlanController@index');
        $router->get('discount-plans/{discountPlanId}', 'DiscountPlanController@show');
        $router->post('discount-plans', 'DiscountPlanController@store');

        $router->group(['middleware' => 'is-pending'], function () use ($router) {
            $router->post('discount-plans/{discountPlanId}/approve', 'DiscountPlanController@approve');
            $router->post('discount-plans/{discountPlanId}/reject', 'DiscountPlanController@reject');
        });

        $router->group(['middleware' => 'is-admin'], function () use ($router) {
            $router->patch('discount-plans/{discountPlanId}', 'DiscountPlanController@update');
            $router->delete('discount-plans/{discountPlanId}', 'DiscountPlanController@destroy');
        });
    });

    /** Billing Metrics */
    $router->group([], function () use ($router) {
        $router->get('billing-metrics', 'BillingMetricController@index');
        $router->get('billing-metrics/{billingMetricId}', 'BillingMetricController@show');
        $router->group(['middleware' => 'is-admin'], function () use ($router) {
            $router->post('billing-metrics', 'BillingMetricController@create');
            $router->patch('billing-metrics/{billingMetricId}', 'BillingMetricController@update');
            $router->delete('billing-metrics/{billingMetricId}', 'BillingMetricController@destroy');
        });
    });

    /** Router Throughput */
    $router->group([], function () use ($router) {
        $router->get('router-throughputs', 'RouterThroughputController@index');
        $router->get('router-throughputs/{routerThroughputId}', 'RouterThroughputController@show');

        $router->group(['middleware' => 'is-admin'], function () use ($router) {
            $router->post('router-throughputs', 'RouterThroughputController@store');
            $router->patch('router-throughputs/{routerThroughputId}', 'RouterThroughputController@update');
            $router->delete('router-throughputs/{routerThroughputId}', 'RouterThroughputController@destroy');
        });
    });

    /** Host */
    $router->group([], function () use ($router) {
        $router->get('hosts', 'HostController@index');
        $router->get('hosts/{id}', 'HostController@show');
        $router->get('hosts/{id}/tasks', 'HostController@tasks');
        $router->post('hosts', 'HostController@store');
        $router->patch('hosts/{id}', 'HostController@update');
        $router->delete('hosts/{id}', 'HostController@destroy');
    });

    /** Host Spec */
    $router->group([], function () use ($router) {
        $router->get('host-specs', 'HostSpecController@index');
        $router->get('host-specs/{hostSpecId}', 'HostSpecController@show');

        $router->group(['middleware' => 'is-admin'], function () use ($router) {
            $router->post('host-specs', 'HostSpecController@store');
            $router->patch('host-specs/{hostSpecId}', 'HostSpecController@update');
            $router->delete('host-specs/{hostSpecId}', 'HostSpecController@destroy');
        });
    });

    /** Host Group */
    $router->group([], function () use ($router) {
        $router->get('host-groups', 'HostGroupController@index');
        $router->get('host-groups/{id}', 'HostGroupController@show');
        $router->get('host-groups/{id}/tasks', 'HostGroupController@tasks');
        $router->post('host-groups', 'HostGroupController@store');
        $router->patch('host-groups/{id}', 'HostGroupController@update');
        $router->delete('host-groups/{id}', 'HostGroupController@destroy');
    });

    /** Images */
    $router->group([], function () use ($router) {
        $router->get('images', 'ImageController@index');
        $router->get('images/{imageId}', 'ImageController@show');
        $router->get('images/{imageId}/parameters', 'ImageController@parameters');
        $router->get('images/{imageId}/metadata', 'ImageController@metadata');

        $router->group(['middleware' => 'is-admin'], function () use ($router) {
            $router->post('images', 'ImageController@store');
        });
        $router->group(['middleware' => 'can-update-image'], function () use ($router) {
            $router->patch('images/{imageId}', 'ImageController@update');
        });
        $router->group(['middleware' => 'can-delete-image'], function () use ($router) {
            $router->delete('images/{imageId}', 'ImageController@destroy');
        });
    });

    /** Image Parameters */
    $router->group(['middleware' => 'is-admin'], function () use ($router) {
        $router->get('image-parameters', 'ImageParameterController@index');
        $router->get('image-parameters/{imageParameterId}', 'ImageParameterController@show');
        $router->post('image-parameters', 'ImageParameterController@store');
        $router->patch('image-parameters/{imageParameterId}', 'ImageParameterController@update');
        $router->delete('image-parameters/{imageParameterId}', 'ImageParameterController@destroy');
    });

    /** Image metadata */
    $router->get('image-metadata', 'ImageMetadataController@index');
    $router->get('image-metadata/{imageMetadataId}', 'ImageMetadataController@show');
    $router->group(['middleware' => 'is-admin'], function () use ($router) {
        $router->post('image-metadata', 'ImageMetadataController@store');
        $router->patch('image-metadata/{imageMetadataId}', 'ImageMetadataController@update');
        $router->delete('image-metadata/{imageMetadataId}', 'ImageMetadataController@destroy');
    });

    /** SSH Key Pairs */
    $router->group([], function () use ($router) {
        $router->group(['middleware' => ['has-reseller-id', 'customer-max-ssh-key-pairs']], function () use ($router) {
            $router->post('ssh-key-pairs', 'SshKeyPairController@create');
        });
        $router->patch('ssh-key-pairs/{keypairId}', 'SshKeyPairController@update');
        $router->get('ssh-key-pairs', 'SshKeyPairController@index');
        $router->get('ssh-key-pairs/{keypairId}', 'SshKeyPairController@show');
        $router->delete('ssh-key-pairs/{keypairId}', 'SshKeyPairController@destroy');
    });

    /** Task */
    $router->group([], function () use ($router) {
        $router->get('tasks', 'TaskController@index');
        $router->get('tasks/{taskId}', 'TaskController@show');
    });

    /** Builder Configurations */
    $router->group(['middleware' => 'is-admin'], function () use ($router) {
        $router->get('builder-configurations', 'BuilderConfigurationController@index');
        $router->get('builder-configurations/{configurationId}', 'BuilderConfigurationController@show');
        $router->get('builder-configurations/{configurationId}/data', 'BuilderConfigurationController@data');
        $router->post('builder-configurations', 'BuilderConfigurationController@store');
        $router->patch('builder-configurations/{configurationId}', 'BuilderConfigurationController@update');
        $router->delete('builder-configurations/{configurationId}', 'BuilderConfigurationController@destroy');
    });
});
