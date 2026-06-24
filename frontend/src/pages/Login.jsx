import { useSearchParams } from 'react-router-dom'
import { loginWithGoogle } from '../api/auth'

const ERROR_MESSAGES = {
  access_denied: 'Your Google account is not authorised to access this application.',
  oauth_failed: 'Sign-in failed or was cancelled. Please try again.',
}

function getErrorMessage(error) {
  if (!error) return null
  return ERROR_MESSAGES[error] ?? 'An error occurred. Please try again.'
}

function Login() {
  const [searchParams] = useSearchParams()
  const error = searchParams.get('error')
  const errorMessage = getErrorMessage(error)

  return (
    <div>
      <h1>Sign in</h1>
      {errorMessage && <p role="alert">{errorMessage}</p>}
      <button type="button" onClick={loginWithGoogle}>
        Sign in with Google
      </button>
    </div>
  )
}

export default Login
