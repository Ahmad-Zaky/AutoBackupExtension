var list = new Array()
var currentPage = 1
var numberPerPage = 10
var numberOfPages = 1
var count = 0
var tableId = ''
var id = 1
var startId = 1

async function load(table_id) {
    result = {};

    if (table_id)
        tableId = table_id

    await $.ajax({
        type: "GET",
        data: { page: currentPage, limit: numberPerPage, count: count },
        success: function (response) {
            result = JSON.parse(response)
        }
    });

    if ((!$.isEmptyObject(result) && result.success === true)) {
        list = result.pageList;
        count = result.count;
        numberOfPages = getNumberOfPages();
        drawList();
        check();
    }
}

function getNumberOfPages() {
    return (parseInt(count)) ? Math.ceil(count / numberPerPage) : 1;
}

function nextPage() {
    currentPage += 1;
    startId = id
    loadList();
}

function previousPage() {
    currentPage -= 1;
    id = startId-numberPerPage;
    startId = id
    loadList();
}

function firstPage() {
    currentPage = 1;
    id = 1;
    loadList();
}

function lastPage() {
    currentPage = numberOfPages;
    id = (count-(count%numberPerPage))+1;
    loadList();
}

function loadList() {
    load();
}

function drawList() {

    parent = document.getElementById(tableId)
    parent.innerHTML = "";
    for (i = 0; i < list.length; i++) {

        element = document.createElement("tr");
        parent.appendChild(element);

        // id
        idTag = document.createElement("td");
        idtext = document.createTextNode(id)
        idTag.appendChild(idtext)
        element.appendChild(idTag)

        listing(element, list[i])

        ++id
    }

}

function listing(element, list) {

    for (const colName in list) {
        filenameTag = document.createElement("td");
        filenameTag.setAttribute('data-name',colName)
        filenametext = document.createTextNode(list[colName])
        filenameTag.appendChild(filenametext)
        element.appendChild(filenameTag)
    }
}

function check() {
    document.getElementById("next").disabled = currentPage == numberOfPages ? true : false;
    document.getElementById("previous").disabled = currentPage == 1 ? true : false;
    document.getElementById("first").disabled = currentPage == 1 ? true : false;
    document.getElementById("last").disabled = currentPage == numberOfPages ? true : false;
}























