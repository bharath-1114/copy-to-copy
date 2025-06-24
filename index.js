
const header = document.querySelector("header");
window.addEventListener("scroll", () => {
   if (window.scrollY >= 200) {
      header.classList.add("sticky");
   } else {
      header.classList.remove("sticky");
   }
});

//-----------------*****************-------------------------------------//

var inputLength = document.getElementById("inputlength");
var message = document.getElementById("message");
var length = document.getElementById("length");

// Show the message when the input field is clicked
inputLength.addEventListener("click", function() {
    if (inputLength.value.length < 8) {
        message.style.display="block";
    }
});

// Hide the message when clicking outside the input field
document.addEventListener("click", function(event) {
    if (!inputLength.contains(event.target)) {
        message.style.display = "none";
    }
});

// Check input length as the user types
inputLength.addEventListener("keyup", function() {
    if (inputLength.value.length >= 8) {
        message.style.display = "none";
    } else {
        message.style.display = "block";
    }
});
//-----------------*****************-------------------------------------//

 // Function to adjust the wave position based on screen size
document.addEventListener("DOMContentLoaded", function() {
   function adjustWavePosition() {
       const wave = document.querySelector('.wave');
       const screenWidth = window.innerWidth;

       if (screenWidth > 1200) {
           wave.style.bottom = "6%"; 
       } else if (screenWidth > 950) {
           wave.style.bottom = "10%"; 
       } else if (screenWidth > 850) {
           wave.style.bottom = "19%"; 
       } else if (screenWidth > 760) {
            wave.style.bottom = "21%";
       } else if (screenWidth > 480) {
           wave.style.bottom = "25%"; 
       } else {
           wave.style.bottom = "29%"; 
       }
   }
   adjustWavePosition();
   window.addEventListener("resize", adjustWavePosition);
});

//-----------------*****************-------------------------------------//

//______________ Upload Files in add this Code..__________________________________//

document.addEventListener("DOMContentLoaded", function () {
   const dropArea = document.getElementById("drop-area");
   const inputFile = document.getElementById("input-file");
   const imageView = document.getElementById("img-view");
   let uploadInprogress = false;

   // Handle file input change event
   if (inputFile) {
       inputFile.addEventListener("change", function () {
           if (inputFile.files.length > 0 && !uploadInprogress) {
               uploadImage(inputFile.files[0]);
           }
       });
   }

   // Handle drag and drop functionality
   if (dropArea) {
       dropArea.addEventListener("dragover", function (e) {
           e.preventDefault();
       });

       dropArea.addEventListener("drop", function (e) {
           e.preventDefault();
           if (!uploadInprogress) {
               inputFile.files = e.dataTransfer.files;
               if (inputFile.files.length > 0) {
                   uploadImage(inputFile.files[0]);
               }
           }
       });
   }

   // Upload and display image
   function uploadImage(file) {
       uploadInprogress = true;
       let imgLink = URL.createObjectURL(file);
       if (imageView) {
           imageView.style.backgroundImage = `url(${imgLink})`;
           imageView.textContent = "";
           imageView.style.border = "none";
       }

       // Simulate a timeout for upload completion
       setTimeout(() => {
           uploadInprogress = false;
       }, 1000);
   }
});
//-----------------*****************-------------------------------------//

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
//-----------------*****************-------------------------------------//

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
//-----------------*****************-------------------------------------//

//______________  Files in DELETE this Code..__________________________________//

document.addEventListener('DOMContentLoaded', function () {
   // Get all table rows
   const rows = document.querySelectorAll('.table tbody tr');
   const deleteButton = document.querySelector('#deleteBtn');
   const fileIdInput = document.getElementById('file_id');


   rows.forEach(row => {
      row.addEventListener('click', function () {
         // Enable the delete button
         deleteButton.disabled = false;

         // Set the file ID in the hidden input
         fileIdInput.value = this.getAttribute('data-id');
      });
   });
});
//-----------------*****************-------------------------------------//

//-----------------*****************-------------------------------------//

const contactForm = document.getElementById("contact-form");
const userName = document.getElementById("username");
const userId = document.getElementById("userid");
const userEmail = document.getElementById("useremail");
const subject = document.getElementById("subject");
const textMessage = document.getElementById("textmessage");

const contactButton = document.getElementById('contbox');
const contactContainer = document.querySelector('.contact-container');

contactButton.addEventListener('click', function() {
    // Get button position and dimensions
    const buttonRect = contactButton.getBoundingClientRect();

    // Set the position of the contact container relative to the button
    contactContainer.style.display = 'flex'; // Show the box

    // Position the box below the button
    contactContainer.style.top = `${buttonRect.bottom + window.scrollY + spacing}px`;  // Set top to be just below the button
    contactContainer.style.left = `${buttonRect.left + window.scrollX}px`;   // Align horizontally with the button


});

// Close the form if clicking outside of it
document.addEventListener('click', function(event) {
    if (!contactContainer.contains(event.target) && !contactButton.contains(event.target)) {
        contactContainer.style.display = 'none'; // Hide the box when clicking outside
    }
});



function sendEmail() {
  // Create the message body
  const bodyMessage = `User Name: ${userName.value} <br>
                       User Id: ${userId.value} <br>
                       User Email: ${userEmail.value} <br> 
                       Message: ${textMessage.value}`;

  // Send the email
  Email.send({
    Host: "smtp.elasticemail.com",
    Username: "copytocopy0@gmail.com",
    Password: "79A6EEB59720689ADF054B0D6FBD0E1A982C",
    To: 'copytocopy0@gmail.com',
    From: "copytocopy0@gmail.com",
    Subject: subject.value,
    Body: bodyMessage
  }).then(
    message => {
      if (message == "OK") {
        Swal.fire({
          title: "success !",
          text: "Message sent successfully!",
          icon: "success"
        });
      }
      document.getElementById('contact-form').reset();
    }
  ).catch(
    error => alert('Error: ' + error)
  );
}

// Prevent form submission and send the email
contactForm.addEventListener("submit", (e) => {
    
    e.preventDefault();
    sendEmail();
});

//-----------------*****************-------------------------------------//