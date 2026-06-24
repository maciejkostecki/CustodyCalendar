function Home({ user, onLogout }) {
  return (
    <div>
      <h1>Welcome, {user.name || user.email}</h1>
      <p>{user.email}</p>
      <button type="button" onClick={onLogout}>
        Log out
      </button>
    </div>
  )
}

export default Home
