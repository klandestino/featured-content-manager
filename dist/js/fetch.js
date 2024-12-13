!function(self){"use strict"
if(!self.fetch){var support_searchParams="URLSearchParams"in self,support_iterable="Symbol"in self&&"iterator"in Symbol,support_blob="FileReader"in self&&"Blob"in self&&function(){try{new Blob
return!0}catch(e){return!1}}(),support_formData="FormData"in self,support_arrayBuffer="ArrayBuffer"in self
if(support_arrayBuffer)var viewClasses=["[object Int8Array]","[object Uint8Array]","[object Uint8ClampedArray]","[object Int16Array]","[object Uint16Array]","[object Int32Array]","[object Uint32Array]","[object Float32Array]","[object Float64Array]"],isDataView=function(obj){return obj&&DataView.prototype.isPrototypeOf(obj)},isArrayBufferView=ArrayBuffer.isView||function(obj){return obj&&viewClasses.indexOf(Object.prototype.toString.call(obj))>-1}
Headers.prototype.append=function(name,value){name=normalizeName(name)
value=normalizeValue(value)
var oldValue=this.map[name]
this.map[name]=oldValue?oldValue+","+value:value}
Headers.prototype.delete=function(name){delete this.map[normalizeName(name)]}
Headers.prototype.get=function(name){name=normalizeName(name)
return this.has(name)?this.map[name]:null}
Headers.prototype.has=function(name){return this.map.hasOwnProperty(normalizeName(name))}
Headers.prototype.set=function(name,value){this.map[normalizeName(name)]=normalizeValue(value)}
Headers.prototype.forEach=function(callback,thisArg){for(var name in this.map)this.map.hasOwnProperty(name)&&callback.call(thisArg,this.map[name],name,this)}
Headers.prototype.keys=function(){var items=[]
this.forEach((function(value,name){items.push(name)}))
return iteratorFor(items)}
Headers.prototype.values=function(){var items=[]
this.forEach((function(value){items.push(value)}))
return iteratorFor(items)}
Headers.prototype.entries=function(){var items=[]
this.forEach((function(value,name){items.push([name,value])}))
return iteratorFor(items)}
support_iterable&&(Headers.prototype[Symbol.iterator]=Headers.prototype.entries)
var methods=["DELETE","GET","HEAD","OPTIONS","POST","PUT"]
Request.prototype.clone=function(){return new Request(this,{body:this._bodyInit})}
Body.call(Request.prototype)
Body.call(Response.prototype)
Response.prototype.clone=function(){return new Response(this._bodyInit,{status:this.status,statusText:this.statusText,headers:new Headers(this.headers),url:this.url})}
Response.error=function(){var response=new Response(null,{status:0,statusText:""})
response.type="error"
return response}
var redirectStatuses=[301,302,303,307,308]
Response.redirect=function(url,status){if(-1===redirectStatuses.indexOf(status))throw new RangeError("Invalid status code")
return new Response(null,{status:status,headers:{location:url}})}
self.Headers=Headers
self.Request=Request
self.Response=Response
self.fetch=function(input,init){return new Promise((function(resolve,reject){var request=new Request(input,init),xhr=new XMLHttpRequest
xhr.onload=function(){var options={status:xhr.status,statusText:xhr.statusText,headers:parseHeaders(xhr.getAllResponseHeaders()||"")}
options.url="responseURL"in xhr?xhr.responseURL:options.headers.get("X-Request-URL")
var body="response"in xhr?xhr.response:xhr.responseText
resolve(new Response(body,options))}
xhr.onerror=function(){reject(new TypeError("Network request failed"))}
xhr.ontimeout=function(){reject(new TypeError("Network request failed"))}
xhr.open(request.method,request.url,!0)
"include"===request.credentials?xhr.withCredentials=!0:"omit"===request.credentials&&(xhr.withCredentials=!1)
"responseType"in xhr&&support_blob&&(xhr.responseType="blob")
request.headers.forEach((function(value,name){xhr.setRequestHeader(name,value)}))
xhr.send(void 0===request._bodyInit?null:request._bodyInit)}))}
self.fetch.polyfill=!0}function normalizeName(name){"string"!=typeof name&&(name=String(name))
if(/[^a-z0-9\-#$%&'*+.\^_`|~]/i.test(name))throw new TypeError("Invalid character in header field name")
return name.toLowerCase()}function normalizeValue(value){"string"!=typeof value&&(value=String(value))
return value}function iteratorFor(items){var iterator={next:function(){var value=items.shift()
return{done:void 0===value,value:value}}}
support_iterable&&(iterator[Symbol.iterator]=function(){return iterator})
return iterator}function Headers(headers){this.map={}
headers instanceof Headers?headers.forEach((function(value,name){this.append(name,value)}),this):Array.isArray(headers)?headers.forEach((function(header){this.append(header[0],header[1])}),this):headers&&Object.getOwnPropertyNames(headers).forEach((function(name){this.append(name,headers[name])}),this)}function consumed(body){if(body.bodyUsed)return Promise.reject(new TypeError("Already read"))
body.bodyUsed=!0}function fileReaderReady(reader){return new Promise((function(resolve,reject){reader.onload=function(){resolve(reader.result)}
reader.onerror=function(){reject(reader.error)}}))}function readBlobAsArrayBuffer(blob){var reader=new FileReader,promise=fileReaderReady(reader)
reader.readAsArrayBuffer(blob)
return promise}function bufferClone(buf){if(buf.slice)return buf.slice(0)
var view=new Uint8Array(buf.byteLength)
view.set(new Uint8Array(buf))
return view.buffer}function Body(){this.bodyUsed=!1
this._initBody=function(body){this._bodyInit=body
if(body)if("string"==typeof body)this._bodyText=body
else if(support_blob&&Blob.prototype.isPrototypeOf(body))this._bodyBlob=body
else if(support_formData&&FormData.prototype.isPrototypeOf(body))this._bodyFormData=body
else if(support_searchParams&&URLSearchParams.prototype.isPrototypeOf(body))this._bodyText=body.toString()
else if(support_arrayBuffer&&support_blob&&isDataView(body)){this._bodyArrayBuffer=bufferClone(body.buffer)
this._bodyInit=new Blob([this._bodyArrayBuffer])}else{if(!support_arrayBuffer||!ArrayBuffer.prototype.isPrototypeOf(body)&&!isArrayBufferView(body))throw new Error("unsupported BodyInit type")
this._bodyArrayBuffer=bufferClone(body)}else this._bodyText=""
this.headers.get("content-type")||("string"==typeof body?this.headers.set("content-type","text/plain;charset=UTF-8"):this._bodyBlob&&this._bodyBlob.type?this.headers.set("content-type",this._bodyBlob.type):support_searchParams&&URLSearchParams.prototype.isPrototypeOf(body)&&this.headers.set("content-type","application/x-www-form-urlencoded;charset=UTF-8"))}
if(support_blob){this.blob=function(){var rejected=consumed(this)
if(rejected)return rejected
if(this._bodyBlob)return Promise.resolve(this._bodyBlob)
if(this._bodyArrayBuffer)return Promise.resolve(new Blob([this._bodyArrayBuffer]))
if(this._bodyFormData)throw new Error("could not read FormData body as blob")
return Promise.resolve(new Blob([this._bodyText]))}
this.arrayBuffer=function(){return this._bodyArrayBuffer?consumed(this)||Promise.resolve(this._bodyArrayBuffer):this.blob().then(readBlobAsArrayBuffer)}}this.text=function(){var rejected=consumed(this)
if(rejected)return rejected
if(this._bodyBlob)return function(blob){var reader=new FileReader,promise=fileReaderReady(reader)
reader.readAsText(blob)
return promise}(this._bodyBlob)
if(this._bodyArrayBuffer)return Promise.resolve(function(buf){for(var view=new Uint8Array(buf),chars=new Array(view.length),i=0;i<view.length;i++)chars[i]=String.fromCharCode(view[i])
return chars.join("")}(this._bodyArrayBuffer))
if(this._bodyFormData)throw new Error("could not read FormData body as text")
return Promise.resolve(this._bodyText)}
support_formData&&(this.formData=function(){return this.text().then(decode)})
this.json=function(){return this.text().then(JSON.parse)}
return this}function Request(input,options){var method,upcased,body=(options=options||{}).body
if(input instanceof Request){if(input.bodyUsed)throw new TypeError("Already read")
this.url=input.url
this.credentials=input.credentials
options.headers||(this.headers=new Headers(input.headers))
this.method=input.method
this.mode=input.mode
if(!body&&null!=input._bodyInit){body=input._bodyInit
input.bodyUsed=!0}}else this.url=String(input)
this.credentials=options.credentials||this.credentials||"omit"
!options.headers&&this.headers||(this.headers=new Headers(options.headers))
this.method=(method=options.method||this.method||"GET",upcased=method.toUpperCase(),methods.indexOf(upcased)>-1?upcased:method)
this.mode=options.mode||this.mode||null
this.referrer=null
if(("GET"===this.method||"HEAD"===this.method)&&body)throw new TypeError("Body not allowed for GET or HEAD requests")
this._initBody(body)}function decode(body){var form=new FormData
body.trim().split("&").forEach((function(bytes){if(bytes){var split=bytes.split("="),name=split.shift().replace(/\+/g," "),value=split.join("=").replace(/\+/g," ")
form.append(decodeURIComponent(name),decodeURIComponent(value))}}))
return form}function parseHeaders(rawHeaders){var headers=new Headers
rawHeaders.replace(/\r?\n[\t ]+/g," ").split(/\r?\n/).forEach((function(line){var parts=line.split(":"),key=parts.shift().trim()
if(key){var value=parts.join(":").trim()
headers.append(key,value)}}))
return headers}function Response(bodyInit,options){options||(options={})
this.type="default"
this.status=void 0===options.status?200:options.status
this.ok=this.status>=200&&this.status<300
this.statusText="statusText"in options?options.statusText:"OK"
this.headers=new Headers(options.headers)
this.url=options.url||""
this._initBody(bodyInit)}}("undefined"!=typeof self?self:this)
