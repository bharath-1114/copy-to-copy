const header = document.querySelector('header');
window.addEventListener("scroll", () => {
   if (window.scrollY >= 200) {
      header.classList.add("sticky");
   } else {
      header.classList.remove("sticky");
   }
})


//______________ Upload Files in add this Code..__________________________________//
const dropArea = document.getElementById("drop-area");
const inputFile = document.getElementById("input-file");
const imageView = document.getElementById("img-view");

let uploadInprogress = false;

inputFile.addEventListener("change", function () {
   if (inputFile.files.length > 0 && !uploadInprogress) {
      uploadImage();

   }
});

function uploadImage() {
   uploadInprogress = true;

   let imgLink = URL.createObjectURL(inputFile.files[0]);
   imageView.style.backgroundImage = `url(${imgLink})`;
   imageView.textContent = "";
   imageView.style.border = 0;

   setTimeout(() => {
         uploadInprogress = false;
      }

      , 1000);
}

dropArea.addEventListener("dragover", function (e) {
   e.preventDefault();
});

dropArea.addEventListener("drop", function (e) {
   e.preventDefault();

   if (!uploadInprogress) {
      inputFile.files = e.dataTransfer.files;

      if (inputFile.files.length > 0) {
         uploadImage();

      }
   }

});


//______________  Files in select this Code..__________________________________//
document.addEventListener('DOMContentLoaded', function () {
   const tableBody = document.querySelector('.table tbody');

   if (tableBody) {
      tableBody.addEventListener('click', function (event) {
         const row = event.target.closest('tr');

         if (row) {
            // Remove 'selected' class from all rows
            const rows = tableBody.querySelectorAll('tr');
            rows.forEach(r => r.classList.remove('selected'));

            // Add 'selected' class to the clicked row
            row.classList.add('selected');
         }
      });
   } else {
      console.error('Table body not found');
   }
});


//______________  Files in EDIT this Code..__________________________________//
document.addEventListener('DOMContentLoaded', function () {
   const rows = document.querySelectorAll('.table tbody tr');
   const editButton = document.getElementById('editBtn');
   let selectedRow = null;

   rows.forEach(row => {
      row.addEventListener('click', function () {
         if (selectedRow) {
            selectedRow.classList.remove('selected');
         }

         selectedRow = this;
         this.classList.add('selected');
         editButton.disabled = false;
         document.getElementById('file_id').value = this.getAttribute('data-id');
      });
   });

   editButton.addEventListener('click', function () {
      if (selectedRow) {
         const currentFilename = selectedRow.querySelector('.filename').textContent.trim();
         const inputHtml = ` <div class="editBox" > 
                                <form id="editForm" > 
                                    <input type="text" name="new_filename" value="${currentFilename}" required> 
                                    <button type="button" class="saveBtn" >Save</button> 
                                    <button type="button" class="cancelBtn" >Cancel</button> 
                                </form> 
                            </div>`;
         selectedRow.querySelector('.filename').innerHTML = inputHtml;

         const saveButton = selectedRow.querySelector('.saveBtn');
         const cancelButton = selectedRow.querySelector('.cancelBtn');

         saveButton.addEventListener('click', function () {
            const newFilename = selectedRow.querySelector('input[name="new_filename"]').value.trim();
            const fileId = selectedRow.getAttribute('data-id');
            const oldFilename = selectedRow.getAttribute('data-filename');

            submitEditForm(fileId, oldFilename, newFilename);
         });

         cancelButton.addEventListener('click', function () {
            resetRow(selectedRow);
            editButton.disabled = true;
         });
      }
   });

   function resetRow(row) {
      const filename = row.getAttribute('data-filename');
      row.querySelector('.filename').textContent = filename;
   }

   function submitEditForm(fileId, oldFilename, newFilename) {
      const form = document.createElement('form');
      form.method = 'POST';
      form.action = ''; // Ensure this points to the correct endpoint
      form.innerHTML = `<input type="hidden" name="file_id" value="${fileId}" > 
                        <input type="hidden" name="old_filename" value="${oldFilename}" > 
                        <input type="hidden" name="new_filename" value="${newFilename}" > 
                        <input type="hidden" name="edit" value="true" > `;
      document.body.appendChild(form);
      form.submit();
   }
});

//______________  Files in DELETE this Code..__________________________________//
document.addEventListener('DOMContentLoaded', function () {
   // Get all table rows
   const rows = document.querySelectorAll('.table tbody tr');
   const deleteButton = document.querySelector('#deleteBtn');
   const fileIdInput = document.getElementById('file_id');

   // Initially disable the delete button
   // deleteButton.disabled = true;

   rows.forEach(row => {
      row.addEventListener('click', function () {
         // Enable the delete button
         deleteButton.disabled = false;

         // Set the file ID in the hidden input
         fileIdInput.value = this.getAttribute('data-id');
      });
   });
});