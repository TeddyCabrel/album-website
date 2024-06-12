// Container we'll use to show an image
let media_popup = document.querySelector('.media-popup');
// The file that is going to be uploaded
let upload_files = [];
// Upload form element
let upload_form = document.querySelector('.upload form');
// Upload media element
let upload_media = document.querySelector('.upload #media');
// Upload drop zone element
let upload_drop_zone = document.querySelector('.upload #drop_zone');
// Handle media preview
const media_preview = file => {
	let reader = new FileReader();
    reader.onload = () => {
		if (file.type.toLowerCase().includes('image')) {
			document.querySelector('.previews').innerHTML += `<div class="preview"><img src="${reader.result}" alt="preview" style="max-height:300px;max-width:100%;"></div>`;
		}
		if (file.type.toLowerCase().includes('audio')) {
			document.querySelector('.previews').innerHTML += `<div class="preview"><audio src="${reader.result}" controls style="width:100px;height:100%"></audio></div>`;
		}
		if (file.type.toLowerCase().includes('video')) {
			document.querySelector('.previews').innerHTML += `<div class="preview"><video src="${reader.result}" style="max-height:300px;width:100%;" controls></video></div>`;
		}
    };
    reader.readAsDataURL(file);
};
// Handle the next and previous buttons
const media_next_prev = media_link => {
	// Retrieve the next and prev elements
	let prev_btn = media_popup.querySelector('.prev');
	let next_btn = media_popup.querySelector('.next');
	// Add the onclick event
	prev_btn.onclick = event => {
		event.preventDefault();
		// Determine the previous element (media)
		let prev_ele = document.querySelector('[data-id="' + media_link.dataset.id + '"]').previousElementSibling;
		// If the prev element exists, click it
		if (prev_ele) prev_ele.click();
	};
	// Add the onclick event
	next_btn.onclick = event => {
		event.preventDefault();
		// Determine the next element (media)
		let next_ele = document.querySelector('[data-id="' + media_link.dataset.id + '"]').nextElementSibling;
		// If the next element exists, click it
		if (next_ele) next_ele.click();
	};
};
// Handle the likes and dislikes
const media_toggle_like = media_link => {
	// Retrieve the like and dislike elements
	let like_btn = media_popup.querySelector('.like');
	// Add the onclick event
	like_btn.onclick = event => {
		event.preventDefault();
		// Use AJAX to update the value in the database
		fetch('like.php?id=' + media_link.dataset.id, { cache: 'no-store' }).then(res => res.text()).then(data => {
			if (data.includes('login')) {
				media_popup.querySelector('.like-count').innerHTML = data;
			} else if (data.includes('unlike')) {
				let likes = parseInt(media_popup.querySelector('.like-count').innerHTML) - 1;
				media_popup.querySelector('.like-count').innerHTML = likes + (likes == 1 ? ' like' : ' likes');
				like_btn.classList.remove('active');
				like_btn.querySelector('i').classList.remove('fa-solid');
				like_btn.querySelector('i').classList.add('fa-regular');
			} else if (data.includes('like')) {
				let likes = parseInt(media_popup.querySelector('.like-count').innerHTML) + 1;
				media_popup.querySelector('.like-count').innerHTML = likes + (likes == 1 ? ' like' : ' likes');
				like_btn.classList.add('active');
				like_btn.querySelector('i').classList.remove('fa-regular');
				like_btn.querySelector('i').classList.add('fa-solid');
			}
		});
	};
};
// Handle media save to collection
const media_save = media_link => {
	// Retrieve the save element
	let save_btn = media_popup.querySelector('.save');
	// If the save button doesn't exist (user isn't loggedin)
	if (!save_btn) return;
	// Add the onclick event
	save_btn.onclick = event => {
		event.preventDefault();
		let userCollections = media_link.dataset.userCollections.split(',,');
		document.body.insertAdjacentHTML('beforebegin', `
			<div class="media-save">
				<div class="con">
					<h2>Add to Collection</h2>
					<form action="add-media-collection.php" method="post" class="gallery-form full">
						<label for="collection">Collection</label>
						<select name="collection" id="collection">
							${userCollections.map(value => '<option value="' + value + '">' + value + '</option>')}
						</select>
						<input type="hidden" name="media_id" value="${media_link.dataset.id}">
						<div class="btn_wrapper">
							<input type="submit" value="Add" class="btn">
							<a href="#" class="btn alt close-btn">Close</a>
						</div>
						<p class="result"></p>
					</form>
				</div>
			</div>
		`);
		document.querySelector('.media-save .close-btn').onclick = event => {
			event.preventDefault();
			document.querySelector('.media-save').remove();
		};
		document.querySelector('.media-save').onsubmit = event => {
			event.preventDefault();
			fetch(document.querySelector('.media-save form').action, {
				method: 'POST',
				body: new FormData(document.querySelector('.media-save form')),
				cache: 'no-store'
			}).then(response => response.text()).then(data => {
				document.querySelector('.media-save .result').innerHTML = data;
			});
		};
	};
};
// If the media popup element exists...
if (media_popup) {
	// Iterate the images and create the onclick events
	document.querySelectorAll('.media-list a').forEach(media_link => {
		// If the user clicks the media
		media_link.onclick = e => {
			e.preventDefault();
			// Retrieve the meta data
			let media_meta = media_link.firstElementChild;
			// Retrieve the like/dislike status for the media
			let media_like_status = media_link.dataset.liked;
			// If the media type is an image
			if (media_link.dataset.type == 'image') {
				// Create new image object
				let img = new Image();
				// Image onload event
				img.onload = () => {
					// Create the pop out image
					media_popup.innerHTML = `
						<a href="#" class="prev${document.querySelector('[data-id="' + media_link.dataset.id + '"]').previousElementSibling ? '' : ' hidden'}"><i class="fas fa-angle-left fa-3x"></i></a>
						<div class="con">
							<h3>${media_link.dataset.title}${media_link.dataset.ownMedia !== undefined ? '<a class="edit-media-btn" href="edit-media.php?id=' + media_link.dataset.id + '"><i class="fa-solid fa-pen"></i></a>' : ''}</h3>
							<p>${media_meta.alt}</p>
							<img src="${img.src}" width="${img.width}" height="${img.height}" alt="">
							<div class="like-con">
								<a href="#" class="like${media_like_status == 1 ? ' active' : ''}"><i class="fa-${media_like_status == 1 ? 'solid' : 'regular'} fa-heart"></i></a>
								<span class="like-count">${media_link.dataset.likes} like${media_link.dataset.likes == 1 ? '' : 's'}</span>
								<div class="action-btns">
									${media_link.dataset.userCollections ? '<a href="#" class="save"><i class="fa-solid fa-bookmark"></i></a>' : ''}
									${media_link.dataset.collection ? '<a href="delete-collection-media.php?collection_id=' + media_link.dataset.collection + '&media_id=' + media_link.dataset.id + '" onclick="return confirm(\'Remove media from collection?\')"><i class="fa-solid fa-trash"></i></a>' : ''}
								</div>
							</div>
						</div>
						<a href="#" class="next${document.querySelector('[data-id="' + media_link.dataset.id + '"]').nextElementSibling ? '' : ' hidden'}"><i class="fas fa-angle-right fa-3x"></i></a>
						<a href="#" class="close"><i class="fa-solid fa-xmark fa-2x"></i></a>
					`;
					media_popup.style.display = 'flex';
					// Prevent portrait images from exceeding the window
					let height = media_popup.querySelector('img').getBoundingClientRect().top - media_popup.querySelector('.con').getBoundingClientRect().top;
					media_popup.querySelector('img').style.maxHeight = `calc(100vh - ${height+150}px)`;
					// Execute the media_mext_prev function
					media_next_prev(media_link);
					// Execute the media_like_dislike function
					media_toggle_like(media_link);
					// Execute the media_save function
					media_save(media_link);
					// Handle the X button in the top right corner
					media_popup.querySelector('.close').onclick = e => {
						e.preventDefault();
						media_popup.style.display = 'none';
						media_popup.innerHTML = '';		
					};
				};
				// Set the image source
				img.src = media_link.dataset.src;
			} else {
				// Determine the media type
	            let type_ele = '';
				// If the media type is a video
	            if (media_link.dataset.type == 'video') {
	                type_ele = `<video src="${media_link.dataset.src}" width="852" height="480" controls autoplay></video>`;
	            }
				// If the media type is a audio file
	            if (media_link.dataset.type == 'audio') {
	                type_ele = `<audio src="${media_link.dataset.src}" controls autoplay></audio>`;
	            }
				// Populate the media
				media_popup.innerHTML = `
					<a href="#" class="prev${document.querySelector('[data-id="' + media_link.dataset.id + '"]').previousElementSibling ? '' : ' hidden'}"><i class="fas fa-angle-left fa-3x"></i></a>
					<div class="con">
						<h3>${media_link.dataset.title}${media_link.dataset.ownMedia !== undefined ? '<a class="edit-media-btn" href="edit-media.php?id=' + media_link.dataset.id + '"><i class="fa-solid fa-pen"></i></a>' : ''}</h3>
						<p>${media_link.dataset.description}</p>
						${type_ele}
						<div class="like-con">
							<a href="#" class="like${media_like_status == 1 ? ' active' : ''}"><i class="fa-${media_like_status == 1 ? 'solid' : 'regular'} fa-heart"></i></a>
							<span class="like-count">${media_link.dataset.likes} like${media_link.dataset.likes == 1 ? '' : 's'}</span>
							<div class="action-btns">
								${media_link.dataset.userCollections ? '<a href="#" class="save"><i class="fa-solid fa-bookmark"></i></a>' : ''}
								${media_link.dataset.collection ? '<a href="delete-collection-media.php?collection_id=' + media_link.dataset.collection + '&media_id=' + media_link.dataset.id + '" onclick="return confirm(\'Remove media from collection?\')"><i class="fa-solid fa-trash"></i></a>' : ''}
							</div>
						</div>
					</div>
					<a href="#" class="next${document.querySelector('[data-id="' + media_link.dataset.id + '"]').nextElementSibling ? '' : ' hidden'}"><i class="fas fa-angle-right fa-3x"></i></a>
					<a href="#" class="close"><i class="fa-solid fa-xmark fa-2x"></i></a>
				`;
				media_popup.style.display = 'flex';
				// Execute the media_next_prev function
				media_next_prev(media_link);
				// Execute the media_like_dislike function
				media_toggle_like(media_link);
				// Execute the media_save function
				media_save(media_link);
				// Handle the X button in the top right corner
				media_popup.querySelector('.close').onclick = e => {
					e.preventDefault();
					media_popup.style.display = 'none';
					media_popup.innerHTML = '';		
				};
			}
		};
	});
	// Hide the image popup container, but only if the user clicks outside the image
	media_popup.onclick = e => {
		if (e.target.className == 'media-popup') {
			media_popup.style.display = 'none';
	        media_popup.innerHTML = '';
		}
	};
}
// Check whether the upload form element exists, which basically means the user is on the upload page
if (upload_form) {
	// Upload form submit event
	upload_form.onsubmit = event => {
		event.preventDefault();
		// Create a new FormData object and retrieve data from the upload form
		let upload_form_date = new FormData(upload_form);
		if (!upload_files.length) {
			document.querySelector('.upload-result').innerHTML = 'Please select a media file!';
		} else {
			for (let i = 0; i < upload_files.length; i++) {
				upload_form_date.append('file_' + i, upload_files[i]);
			}
			upload_form_date.append('total_files', upload_files.length);
			// Create a new AJAX request
			let request = new XMLHttpRequest();
			// POST request
			request.open('POST', upload_form.action);
			// Add the progress event
			request.upload.addEventListener('progress', event => {
				// Update the submit button with the current upload progress in percent format
				document.querySelector('.upload-result').innerHTML = 'Uploading... ' + '(' + ((event.loaded/event.total)*100).toFixed(2) + '%)';
				// Disable the submit button
				upload_form.querySelector('#submit_btn').disabled = true;
			});
			// Check if the upload is complete or if there are any errors
			request.onreadystatechange = () => {
				if (request.readyState == 4 && request.status == 200) {
					// Upload is complete
					if (request.responseText.includes('Complete')) {
						// Output the successful response
						upload_form.querySelector('#submit_btn').value = request.responseText;
					} else {
						// Output the errors
						upload_form.querySelector('#submit_btn').disabled = false;
						document.querySelector('.upload-result').innerHTML = request.responseText;
					}
				}
			};
			// Send the request
			request.send(upload_form_date);
		}
	};
	// On media change, display the thumbnail form element, but only if the media type is either a video or image
	upload_media.onchange = () => {
		for (let i = 0; i < upload_media.files.length; i++) {
			// Show preview
			media_preview(upload_media.files[i]);
			document.querySelector("#submit_btn").insertAdjacentHTML('beforebegin', `
				${document.querySelector('#title_0') ? '<div class="separator"></div>' : ''}

				<label for="title_${upload_files.length}">Title ${1+upload_files.length}</label>
				<input type="text" id="title_${upload_files.length}" name="title_${upload_files.length}" id="title" value="${upload_media.files[i].name}" placeholder="Title ${1+upload_files.length}" required>
		
				<label for="description_${upload_files.length}">Description ${1+upload_files.length}</label>
				<textarea id="description_${upload_files.length}" name="description_${upload_files.length}" id="description" placeholder="Description ${1+upload_files.length}"></textarea>
		
				<label for="thumbnail_${upload_files.length}" class="thumbnail_${upload_files.length}">Thumbnail ${1+upload_files.length}</label>
				<input type="file" id="thumbnail_${upload_files.length}" name="thumbnail_${upload_files.length}" accept="image/*" id="thumbnail" class="thumbnail thumbnail_${upload_files.length}">

				<label for="public_${upload_files.length}">Who can view your media ${1+upload_files.length}?</label>
				<select id="public_${upload_files.length}" name="public_${upload_files.length}" type="text" required>
					<option value="1">Everyone</option> 
					<option value="0">Only Me</option>
				</select>

				<label for="collection">Collection ${1+upload_files.length}</label>
				<select id="collection" name="collection">
					<option value="">(none)</option>
					${upload_form.dataset.userCollections.split(',,').map(item => '<option value="' + item + '">' + item + '</option>')}
				</select>
			`);
			if (upload_media.files[i].type.toLowerCase().includes('audio') || upload_media.files[i].type.toLowerCase().includes('video')) {
				document.querySelectorAll('.thumbnail_' + upload_files.length).forEach(el => el.style.display = 'flex');
			} else {
				document.querySelectorAll('.thumbnail_' + upload_files.length).forEach(el => el.style.display = 'none');
			}
			upload_files.push(upload_media.files[i]);
		}
	};
	// On drag and drop media file, do the same as the above code, but in addition, update the media file variable
	upload_drop_zone.ondrop = event => {
		event.preventDefault();
		for (let i = 0; i < event.dataTransfer.items.length; i++) {
			if (event.dataTransfer.items && event.dataTransfer.items[i].kind === 'file') {
				// Get file
				let file = event.dataTransfer.items[i].getAsFile();
				if (file.type.toLowerCase().includes('audio') || file.type.toLowerCase().includes('video')) {
					document.querySelectorAll('.thumbnail_' + upload_files.length).forEach(el => el.style.display = 'flex');
				} else {
					document.querySelectorAll('.thumbnail_' + upload_files.length).forEach(el => el.style.display = 'none');
				}
				// Show preview
				media_preview(file);
				document.querySelector("#submit_btn").insertAdjacentHTML('beforebegin', `
					${document.querySelector('#title_0') ? '<div class="separator"></div>' : ''}

					<label>Title ${1+upload_files.length}</label>
					<input type="text" name="title_${upload_files.length}" id="title" value="${file.name}" placeholder="Title ${1+upload_files.length}" required>
			
					<label>Description ${1+upload_files.length}</label>
					<textarea name="description_${upload_files.length}" id="description" placeholder="Description ${1+upload_files.length}"></textarea>
			
					<label for="thumbnail" class="thumbnail thumbnail_${upload_files.length}">Thumbnail ${1+upload_files.length}</label>
					<input type="file" name="thumbnail_${upload_files.length}" accept="image/*" id="thumbnail" class="thumbnail">

					<label for="public_${upload_files.length}">Who can view your media ${1+upload_files.length}?</label>
					<select id="public_${upload_files.length}" name="public_${upload_files.length}" type="text" required>
						<option value="1">Everyone</option> 
						<option value="0">Only Me</option>
					</select>

					<label for="collection">Collection ${1+upload_files.length}</label>
					<select id="collection" name="collection">
						<option value="">(none)</option>
						${upload_form.dataset.userCollections.split(',,').map(item => '<option value="' + item + '">' + item + '</option>')}
					</select>
				`);
				upload_files.push(file);
			}
		}
	};
	// Dragover drop zone event
	upload_drop_zone.ondragover = event => {
		event.preventDefault();
		// Update the element style; add CSS class
		upload_drop_zone.classList.add('dragover');
	};
	// Dragleave drop zone event
	upload_drop_zone.ondragleave = event => {
		event.preventDefault();
		// Update the element style; remove CSS class
		upload_drop_zone.classList.remove('dragover');
	};
	// Click drop zone event
	upload_drop_zone.onclick = event => {
		event.preventDefault();
		// Click the media file upload element, which will show the open file dialog
		document.querySelector('.upload #media').click();
	}
}