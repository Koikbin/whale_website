const input = document.querySelector('input')
const send = document.querySelector('button')
const chatContainer = document.querySelector('.chats')
send.onclick = () => {
    if(input.value){
        const message = `
          <div class="message">
              <div>
                ${input.value}
              </div>
          </div>
        `
        chatContainer.innerHTML += message;
        scrollDown();
        async_handleUserQuestion(input.value);
        input.value = null;
    }
}

async function async_handleUserQuestion(user_query) {
  try {
    var x = await handleUserQuestion(10); // Assuming the function doesn't need an argument
    handleAnswer(x, user_query);
  } catch (error) {
    console.error('Error handling user question:', error);
    // Optionally, inform the user that an error occurred.
  }
}

// when click enter
input.addEventListener("keypress", function(e){
    if(e.key === "Enter"){
        e.preventDefault();
        send.click();
    }
})

function handleUserQuestion(){
  return new Promise((resolve, reject) => {
    var http = new XMLHttpRequest();
    var data = new FormData();
    data.append('query', input.value);
    http.open('POST', 'user_query_embedding.php', true);
    http.send(data);
    http.onload = () => {
      if(http.status >= 200 && http.status < 300) {
        resolve(http.response);
      } else {
        reject(http.statusText);
      }
    };
    http.onerror = () => reject(http.statusText);
  });
}

// get answer from here.
function handleAnswer(context, user_query){
  console.log(context);
  var http = new XMLHttpRequest()
    var data = new FormData()
    data.append('prompt', input.value)
    data.append('query', user_query)
    data.append('context', context)
    http.open('POST', 'request.php', true)
    http.send(data)
    setTimeout(() => {
        chatContainer.innerHTML += `
            <div class="message response">
                <div>
                    <img src="img/preloader.gif" alt="preloader">
                </div>
            </div>
        `
        scrollDown();
    }, 1000);
    http.onload = () => {
        var replyText = http.response;
        let replyContainer = document.querySelectorAll('.response');
        // console.log(replyText);
        console.log(typeof(http.response));      
        console.log(replyContainer);
        console.log(replyContainer.length);
        
        // replyText will be shown as the answer by the chat robot.
        replyContainer[replyContainer.length-1].querySelector('div').innerHTML = replyText
       
    }
}


// scroll down when new message added
function scrollDown(){
  chatContainer.scrollTop = chatContainer.scrollHeight;
}

function similarity() {
var xhttp = new XMLHttpRequest();
xhttp.open("GET", "getData.php", true);
xhttp.send();
xhttp.onload = () => {
      console.log(xhttp.response);
}
}


// scroll down when new message added
function scrollDown(){
    chatContainer.scrollTop = chatContainer.scrollHeight;
}

function loadData() {
  var xhttp = new XMLHttpRequest();
  xhttp.onreadystatechange = function() {
    if (this.readyState == 4 && this.status == 200) {
      var response = JSON.parse(this.responseText);
      var output = '';
      for(var i = 0; i < response.length; i++) {
        output += 'id: ' + response[i].id + ' - Data: ' + response[i].data + '<br>';
      }
      document.getElementById("data").innerHTML = output;
    }
  };
}