# *each*; a JSON patch extension for merging arrays and objects
JSON patch
([RFC 6902 specs](https://tools.ietf.org/html/rfc6902);
[tutorial](https://sookocheff.com/post/api/understanding-json-patch/))
is a method for modifying JSON data structures. I like that JSON patch is more
regular than JSON merge patch
([RFC 7386 specs](https://tools.ietf.org/html/rfc7386);
[tutorial](https://erosb.github.io/post/json-patch-vs-merge-patch/)),
but it's a bit limited for merging arrays and objects together. 
This repo describes an extension of a JSON patch with a member called
*each*, which offers capabilities for merging arrays and objects. 
JSON patches that are extended with *each* can be translated 
to traditional JSON patches.
Below is a description of how *each* works, while you can find a PHP 
implementation of it in the repo.

# In-a-nutshell
If *each* is true, and...
* value is an array, then the operation is applied to the target path for each element of the value
* value is an object, then the operation is applied to each member of the target path for each corresponding member of the value

In both cases, the operation is applied non-recursively, i.e., it is applied to the children of the target path, but not to any grandchildren.

# How *each* works with arrays
Imagine we'd like to patch this data structure:
```javascript
["s1", "s2", "s3"]
```
## Traditional JSON patch
If we apply this traditional JSON patch:
```javascript
{
  "value": ["t1", "t2"],
  "path": "/-",
  "op": "add"
}
```
Then the result would be
```javascript
["s1", "s2", "s3", ["t1", "t2"]]
```
## JSON patch extended with *each*
However, if we would apply this extended JSON patch
```javascript
{
  "value": ["t1", "t2"],
  "path": "/-",
  "op": "add",
  "each": true
}
```
Then the patch operation is applied for each element of value, which translates to a traditional JSON patch as follows
```javascript
[{
  "value": "t1",
  "path": "/-",
  "op": "add"
},{
  "value": "t2",
  "path": "/-",
  "op": "add"
}]
```
And the result would be
```javascript
["s1", "s2", "s3", "t1", "t2"]
```
## Using *each* with a numbered index
If providing a numbered index, then the value array is inserted at the target path in whole. For example, if we apply this extended JSON patch
```javascript
{
  "value": ["t1", "t2"],
  "path": "/1",
  "op": "add",
  "each": true
}
```
It translates to traditional JSON patching as follows
```javascript
[{
  "value": "t1",
  "path": "/1",
  "op": "add"
},{
  "value": "t2",
  "path": "/2",
  "op": "add"
}]
```
And the result would be
```javascript
["s1", "t1", "t2", "s2", "s3"]
```

# How *each* works with objects
Imagine we'd like to patch this data structure:
```javascript
{"p1": "s1", "p2": "s2"}
```
## Traditional JSON patch
If we apply this traditional JSON patch:
```javascript
{
  "value": {"q1": "t1", "q2": "t2"},
  "path": "",
  "op": "add"
}
```
Then the result would be
```javascript
{"q1": "t1", "q2": "t2"}
```
## JSON patch extended with *each*
However, if we would apply this extended JSON patch
```javascript
{
  "value": {"q1": "t1", "q2": "t2"},
  "path": "",
  "op": "add",
  "each": true
}
```
Then the patch operation is applied for each property of value, which translates to a traditional JSON patch as follows
```javascript
[{
  "value": "t1",
  "path": "/q1",
  "op": "add"
},{
  "value": "t2",
  "path": "/q2",
  "op": "add"
}]
```
And the result would be
```javascript
{"p1": "s1", "p2": "s2", "q1": "t1", "q2": "t2"}
```

# Final Words
* The PHP implementation can be found in `each_implementation.php`. 
It provides a function that converts an JSON patch extended with *each* to a 
traditional JSON patch. The extension is illustrated by converting 
some JSON patches and applying them using the 
[json-diff](https://github.com/swaggest/json-diff) library.