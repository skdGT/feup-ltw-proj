document.querySelectorAll('#pet_add_favorite_button').forEach(button => { //add buttons on pet_list or pet_detail
    button.addEventListener('click', addFavorite);
})

document.querySelectorAll('#pet_remove_favorite_button').forEach(button => { //add buttons on pet_list or pet_detail
    button.addEventListener('click', removeFavorite);
})

document.querySelectorAll('#pet_proposal_button').forEach(button => { //submit proposal button on pet_detail
    button.addEventListener('click', petProposal);
})

document.querySelectorAll('#accept_proposal_button').forEach(button => { //submit proposal button on pet_detail
    button.addEventListener('click', acceptProposal);
})

document.querySelectorAll('#deny_proposal_button').forEach(button => { //submit proposal button on pet_detail
    button.addEventListener('click', denyProposal);
})

document.querySelectorAll('#delete-pet-button').forEach(button => { //submit proposal button on pet_detail
    button.addEventListener('click', deletePet);
})

// Send message
function addFavorite(event) {
    let csrf = document.querySelector('#csrf_var').value;
    let request = new XMLHttpRequest();

    request.open('post', '../actions/action_add_favorite.php', true)
    request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded')
    request.send(encodeForAjax({'csrf':csrf, 'id': event.target.getAttribute("data-petid")}))

    //removes add button and adds remove button
    let pet_index = event.target.getAttribute("data-index");
    let pet_petid = event.target.getAttribute("data-petid");

    event.target.remove();

    let remove_button = document.createElement("button");
    remove_button.setAttribute('id',"pet_remove_favorite_button");
    remove_button.setAttribute('name',"favorite");
    remove_button.setAttribute('data-index',pet_index);
    remove_button.setAttribute('data-petid',pet_petid);
    remove_button.textContent = "Remove Favorite";
    document.getElementsByClassName('buttons')[pet_index].appendChild(remove_button);
    remove_button.addEventListener('click', removeFavorite);
    event.preventDefault();
}

function removeFavorite(event) {
    let csrf = document.querySelector('#csrf_var').value;

    let request = new XMLHttpRequest();
    request.open('post', '../actions/action_remove_favorite.php', true)
    request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded')
    request.send(encodeForAjax({'csrf':csrf, 'id': event.target.getAttribute("data-petid")}))

    let pet_index = event.target.getAttribute("data-index");
    let pet_petid = event.target.getAttribute("data-petid");

    //removes add button and adds remove button
    event.target.remove();

    let add_button = document.createElement("button");
    add_button.setAttribute('id',"pet_add_favorite_button");
    add_button.setAttribute('name',"favorite");
    add_button.setAttribute('data-index',pet_index);
    add_button.setAttribute('data-petid',pet_petid);
    add_button.textContent = "Add to Favorites";
    document.getElementsByClassName('buttons')[pet_index].appendChild(add_button);
    add_button.addEventListener('click', addFavorite);

    event.preventDefault();
}

function encodeForAjax(data) {
    return Object.keys(data).map(function(k){
        return encodeURIComponent(k) + '=' + encodeURIComponent(data[k])
    }).join('&');
}

function petProposal() {
    let proposals = document.getElementById("proposals")
    let comments = document.getElementById("pet-comments")
    let make_proposal = document.getElementById("make_proposal")

    if (proposals.style.display !== "none"){
        proposals.style.display = "none"
        comments.style.display = "none"
        make_proposal.style.display = "block"
    } else {
        make_proposal.style.display = "none"
        proposals.style.display = "block"
        comments.style.display = "block"
    }
}

function editUserInfo() {
    let checkBox = document.getElementById("about_me_check")
    let icon = document.getElementById("abt_icon")
    let pets = document.getElementsByClassName("user_pets")[0]
    let aboutme = document.getElementsByClassName("edit_user_info")[0]

    if (checkBox.checked){
        icon.className = "fas fa-file"
        pets.style.display = "none"
        aboutme.style.display = "block"
    } else {
        icon.className = "far fa-file"
        pets.style.display = "block"
        aboutme.style.display = "none"
    }
}

function editShelterInfo() {
    let checkBox = document.getElementById("about_me_check")
    let icon = document.getElementById("abt_icon")
    let pets = document.getElementsByClassName("shelter_adoption")[0]
    let aboutme = document.getElementsByClassName("edit_shelter_info")[0]

    if (checkBox.checked){
        icon.className = "fas fa-file"
        pets.style.display = "none"
        aboutme.style.display = "block"
    } else {
        icon.className = "far fa-file"
        pets.style.display = "block"
        aboutme.style.display = "none"
    }
}

function acceptProposal(event){
    let pet_id = event.target.getAttribute('data-petid');
    let prop_id = event.target.getAttribute('data-userid');

    let request = new XMLHttpRequest();
    request.open('get', '../actions/action_accept_proposal.php?' + encodeForAjax({'prop_id': prop_id, 'pet_id': pet_id}), true);
    request.send();

}

function denyProposal(event){
    let pet_id = event.target.getAttribute('data-petid');
    let prop_id = event.target.getAttribute('data-userid');

    let request = new XMLHttpRequest();
    request.open('get', '../actions/denyProposal.php?' + encodeForAjax({'prop_id': prop_id, 'pet_id': pet_id}), true);
    request.send();

}

function deletePet(event){
    let pet_id = event.target.getAttribute('data-petid');

    let request = new XMLHttpRequest();
    request.open('get', '../actions/deletePet.php?' + encodeForAjax({'pet_id': pet_id}), true);
    request.send();

    window.location.href = "pets_list.php";
}