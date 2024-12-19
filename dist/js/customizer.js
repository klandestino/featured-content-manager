!function(wp){wp.customize.FeaturedAreaControl=wp.customize.Control.extend({ready:function(){const control=this,container=this.container[0],featuredAreaList=container.querySelector("ol.nested-sortable"),addItemButtons=container.querySelectorAll(".add-featured-item"),clearOverflowButton=container.querySelector(".clear-overflow"),{__:__,_x:_x,_n:_n,_nx:_nx}=wp.i18n
let featuredArea,settings_timer,search_timer,max,type,subtype
class FeaturedItem{constructor(data,list,parent){this.data=data
this.list=list
this.parent=parent
this.element=null
this.isFeaturedItemObject(this.data)&&this.addItem()}isFeaturedItemObject(obj){return obj.hasOwnProperty("id")&&obj.hasOwnProperty("title")&&obj.hasOwnProperty("id")}addItem(){0===this.data.title.length&&(this.data.title="(no title)")
let innerHTML=wp.template("featured-item")(this.data)
this.element=function(html){var template=document.createElement("template")
html=html.trim()
template.innerHTML=html
return template.content.firstChild}(innerHTML)
this.element.querySelector(".button-link-delete").addEventListener("click",(event=>this.deleteItem(event)))
this.element.querySelector(".featured-item-add").addEventListener("click",(event=>this.cloneItem(event)))
this.element.querySelector(".handle").addEventListener("click",(()=>{Array.isArray(JSON.parse(featuredAreaList.dataset.settings))||this.element.classList.toggle("open")}))
this.addSettings(this.element)
let nestedSortable=this.element.querySelector(".nested-sortable")
featuredArea.initSortable(nestedSortable)
if(void 0!==this.parent&&featuredAreaList.dataset.levels>1){this.list.querySelector('[data-id="'+this.parent+'"] ol').appendChild(this.element)}else this.list.appendChild(this.element)
"object"==typeof this.data.children&&this.data.children.forEach((child=>{new FeaturedItem(child,this.list,this.data.id)}))}addSettings(element){let settings=JSON.parse(featuredAreaList.dataset.settings),data=this.data
Object.keys(settings).forEach((key=>{let setting=settings[key],setting_key=key
if("select"===setting.type){let selectList=document.createElement("select")
Object.keys(setting.values).forEach((key=>{let option=setting.values[key]
var optionElement=document.createElement("option")
optionElement.value=key
optionElement.text=option
optionElement.selected=data[setting_key]===key
selectList.appendChild(optionElement)}))
void 0!==data[setting_key]&&(element.dataset[key]=data[setting_key])
element.querySelector(".settings").appendChild(selectList)
element.addEventListener("change",(event=>{element.dataset[key]=event.target.value
this.data[key]=event.target.value
featuredArea.setSettings()}))}}))}removeItem(){this.element.remove()}cloneItem(event){let item=new FeaturedItem(this.data,featuredAreaList)
featuredArea.toggleSearchPanel(event)
if(featuredArea.isDuplicate(this.data)){featuredArea.addErrorNotification("This item already exist in the selected featured area.")
item.removeItem()}else if(featuredArea.isFull()){featuredArea.addErrorNotification("The selected featured area is full.")
item.removeItem()
return}featuredArea.setSettings()}deleteItem(){featuredAreaList.querySelector('[data-id="'+this.data.id+'"]').remove()
featuredArea.setSettings()}}class FeaturedItemSearch{constructor(featuredArea){this.featuredArea=featuredArea
this.featuredAreaItems=[]
this.active=!0
this.searchResult=document.getElementById("featured-items-search-list")
this.search("")
document.getElementById("featured-items-search-input").addEventListener("keyup",(event=>this.onInputChange(event)))
document.addEventListener("click",(event=>{event.target.classList.contains("add-featured-item")||isChildOf(event.target,"featured-item-container")||this.close()}))
document.querySelector("#featured-items-search-panel .customize-section-back").addEventListener("click",(event=>this.close()))}setItems(settings){try{settings=JSON.parse(settings)}catch(e){console.log(e)
settings=[{}]}settings=settings.slice(0,50)
const items=[]
settings.forEach((item=>{null!=item&&items.push(parseInt(item.id))}))
this.featuredAreaItems=items}open(settings){document.querySelector("body").classList.add("adding-featured-items")
this.active=!0
this.setItems(settings)
this.search("")}close(){document.querySelector("body").classList.remove("adding-featured-items")
this.active=!1
this.clear()}toggle(settings){document.querySelector("body").classList.contains("adding-featured-items")?this.close():this.open(settings)}clear(){this.active=!1
document.getElementById("featured-items-search-input").value=""}onInputChange(event){event.preventDefault()
const search=event.target.value
this.search(search)}search(search){if(this.active){clearTimeout(search_timer)
search_timer=setTimeout((()=>{const body=document.querySelector("body")
body.classList.add("searching")
var search_item_tpl=this.searchResult.querySelectorAll(".featured-item-tpl");[].forEach.call(search_item_tpl,(function(item){item.remove()}))
window.fetch(wpApiSettings.root+wpFeaturedContentApiSettings.base+"posts",{method:"POST",headers:{Accept:"application/json","Content-Type":"application/json","X-WP-Nonce":wpApiSettings.nonce},body:JSON.stringify({s:search,type:type,subtype:subtype,list:this.featuredArea,items:this.featuredAreaItems}),credentials:"same-origin"}).then((data=>data.json())).then((data=>{body.classList.remove("searching")
data.forEach(((obj,index)=>{new FeaturedItem(obj,this.searchResult)}))}))}),500)}}}featuredArea=new class{constructor(){this.nestedSortables=[]
max=featuredAreaList.dataset.max
type=featuredAreaList.dataset.type
subtype=featuredAreaList.dataset.subtype.split(",")
addItemButtons.forEach((button=>{button.addEventListener("click",(event=>this.toggleSearchPanel(event)))}))
clearOverflowButton.addEventListener("click",(event=>this.clearOverflow(event)))}loadSettings(){let settings=control.setting.get()
try{settings=JSON.parse(settings)}catch(e){console.log(e)
settings=[{}]}settings=settings.slice(0,50)
settings.forEach((item=>{null!=item&&new FeaturedItem(item,featuredAreaList)}))
this.toggleClearOverflow()
this.initSortables()}getDataAttributes(dataset){return Object.keys(dataset).reduce((function(object,key){object[key]=dataset[key]
return object}),{})}setSettings(){clearTimeout(settings_timer)
this.toggleClearOverflow()
settings_timer=setTimeout((()=>{let settings=this.serialize(featuredAreaList)
control.setting.set(JSON.stringify(settings))
wp.customize.previewer.refresh()}),500)}serialize(sortable){var serialized=[],children=[].slice.call(sortable.children)
for(var i in children){var nested=children[i].querySelector(".nested-sortable")
let attributes=this.getDataAttributes(children[i].dataset)
serialized.push({...attributes,children:nested?this.serialize(nested):[]})}return serialized}isDuplicate(obj){let result=!1
featuredAreaList.querySelectorAll('[data-id="'+obj.id+'"]').length>1&&(result=!0)
return result}isFull(){let children=featuredAreaList.querySelectorAll(".featured-item-tpl")
return max<children.length}toggleSearchPanel(event){event.preventDefault()
if(this.searchPanel)this.searchPanel.toggle(control.setting.get())
else{this.searchPanel=new FeaturedItemSearch(featuredAreaList.id)
this.searchPanel.toggle(control.setting.get())}}toggleClearOverflow(){this.isFull()?clearOverflowButton.classList.remove("hidden"):clearOverflowButton.classList.add("hidden")}clearOverflow(event){event.preventDefault();[].slice.call(featuredAreaList.querySelectorAll(".featured-item-tpl")).splice(max).forEach((child=>{child.remove()
this.setSettings()}))}initSortables(){let featuredAreaList=container.querySelector(".featured-area")
this.initSortable(featuredAreaList)
let searchList=document.querySelector("#featured-items-search-list")
this.initSortable(searchList,{group:{name:"nested",put:!1},animation:150,sort:!1})}initSortable(sortable,args={group:"nested",swapThreshold:.65,emptyInsertThreshold:5,animation:150,onSort:event=>{this.setSettings()},onAdd:event=>{if(this.isDuplicate(event.clone.dataset)){event.item.remove()
this.addErrorNotification("This item already exist in the selected featured area.")}}}){new Sortable(sortable,args)}addErrorNotification(message){wp.customize.notifications.add("error",new wp.customize.Notification("error",{dismissible:!0,message:__(message,"featured-content-manager"),type:"error"}))}}
featuredArea.loadSettings()}})
_.extend(wp.customize.controlConstructor,{"featured-area":wp.customize.FeaturedAreaControl})
function isChildOf(element,classname){return!!element.parentNode&&(element.className.split(" ").indexOf(classname)>=0||isChildOf(element.parentNode,classname))}}(wp,jQuery)
!function(api){const sectionsWithGoToUrls=wpFeaturedContentApiSettings.go_to_urls
for(const[key,value]of Object.entries(sectionsWithGoToUrls))api.section(key,(function(section){section.expanded.bind((function(isExpanded){isExpanded?api.previewer.previewUrl.set(api.settings.url.home+value):api.previewer.previewUrl.set(api.settings.url.home)}))}))}(wp.customize)
