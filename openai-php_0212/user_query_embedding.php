<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "open_vector_test";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}


$sql = "SELECT * FROM class_embedding";
$result = $conn->query($sql);

$data_class = array();
if ($result->num_rows > 0) {
  while($row = $result->fetch_assoc()) {
    $data_class[] = $row;
  }
} else {
  echo json_encode([]);
}

require 'vendor/autoload.php';
$apiKey = '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';
$user_query = $_POST['query'];
$textToEmbed = "Example text for embedding";

$user_query_embedding = generateEmbedding($apiKey, $user_query);
if ($user_query_embedding) {
  echo "user query embedding successfully generated.\r\n";
} else {
  echo "Failed to generate embedding.";
}

// determine the topic the query belongs to.
$query_topic = getTopic($data_class, $user_query_embedding);
print_r("this is the title for user's query: ".$query_topic."\n");

if (preg_match('/^[a-zA-Z0-9_]+$/', $query_topic)) {
  $stmt = $conn->prepare("SELECT * FROM embeddings WHERE title = ?");
  $stmt->bind_param("s", $query_topic); // 's' specifies the variable type => 'string'
  $stmt->execute();
  $result = $stmt->get_result();

  $data_on_topic = array();
  if ($result && $result->num_rows > 0) {
      // Process each row of the result
      while ($row = $result->fetch_assoc()) {
          $data_on_topic[] = $row;
      }
  } else {
      echo "0 results found.";
  }
  $stmt->close();
} else {
  echo "Invalid title provided.";
}

// print_r($data_on_topic);
$answer = getAnswer($data_on_topic, $user_query_embedding);
print_r("this is answer:");
print_r($answer[0]["text"]);
echo $answer[0]["text"];


$conn->close();

// calculate the similarity, then put the similarity to be an element of the array, then sort the array based on the similarity.
function getTopic($data, $user_query_embedding)
{
  $results = [];
  for ($i = 0; $i < count($data); $i++) {
      $data_embedding = explode(", ", $data[$i]["embeddings"]);
      $similarity = cosineSimilarity($data_embedding, $user_query_embedding);
      // store the simliarty and index in an array and sort by the similarity
      $results[] = [
          'similarity' => $similarity,
          'index' => $data[$i]["id"],
          'title' => $data[$i]["title"],
          'text' => $data[$i]["text"],
      ];
  }
  // print_r($results);
  usort($results, function ($a, $b) {
      return $a['similarity'] <=> $b['similarity'];
  });
  $topic = str_replace(".txt", "", end($results)['title']);
  return $topic;
}

function getAnswer($data, $user_query_embedding)
{
  $results = [];
  for ($i = 0; $i < count($data); $i++) {
    $data_embedding = explode(", ", $data[$i]["embeddings"]);
    $similarity = cosineSimilarity($data_embedding, $user_query_embedding);
    // store the simliarty and index in an array and sort by the similarity
    $results[] = [
        'similarity' => $similarity,
        'index' => $data[$i]["id"],
        'title' => $data[$i]["title"],
        'n_tokens' => $data[$i]["n_tokens"],
        'text' => $data[$i]["text"],
    ];
  }
  $cur_len = 0;
  $max_len = 10000;

  usort($results, function ($a, $b) {
      return $a['similarity'] <=> $b['similarity'];
  });
  // print_r($results);
  $len = count($results);
  $i = 0;

  $data_high_similartity = array();
  while($i < $len && $cur_len < $max_len){
    $cur_len += $results[$len - 1 - $i]["n_tokens"] + 4;
    if ($cur_len > $max_len) {
      break;
    }
    // if similarity too small, ignore the page.
    if ($results[$len - 1 - $i] < 0.7) {
      break;
    }
    array_push($data_high_similartity, $results[$len - 1 - $i]);
    $i++;
  }

  $len = count($data_high_similartity);
  $data_high_similartity_str = "";
  for($i = 0; $i < $len; $i++){
    $data_high_similartity_str = $data_high_similartity_str . $data_high_similartity[$i]["text"];
  }
  return $data_high_similartity;
}

function cosineSimilarity($u, $v)
{
  $dotProduct = 0;
  $uLength = 0;
  $vLength = 0;
  
  for ($i = 0; $i < count($u); $i++) {
      $lf = (float)$u[$i];
      $rf = (float)$v[$i];
      $dotProduct += $lf * $rf;
      $uLength += $lf * $lf;
      $vLength += $rf * $rf;
  }
  $uLength = sqrt($uLength);
  $vLength = sqrt($vLength);
  return $dotProduct / ($uLength * $vLength);
}
?>

<!-- below is the generation of the embeddings of user's queries. -->
<?php
  function generateEmbedding($apiKey, $text) {
    $ch = curl_init();
    $data = json_encode([
        'model' => 'text-embedding-ada-002',
        'input' => $text
    ]);
    
    curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/embeddings');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);
    
    $result = curl_exec($ch);
    curl_close($ch);
    
    if (!$result) {
        die("Error: API request failed.");
    }
    
    $resultArray = json_decode($result, true);
    return $resultArray['data'][0]['embedding'] ?? null;
}
?>
<!-- handling user's query by first generating embedding -->